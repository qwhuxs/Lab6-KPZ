<?php
require_once __DIR__ . '/core/interfaces/DrawingToolInterface.php';
require_once __DIR__ . '/core/interfaces/CommandInterface.php';
require_once __DIR__ . '/core/interfaces/StateSaverInterface.php';

require_once __DIR__ . '/core/patterns/ToolFactory.php';
require_once __DIR__ . '/core/patterns/DrawingStrategy.php';
require_once __DIR__ . '/core/patterns/DrawingCommand.php';
require_once __DIR__ . '/core/patterns/CommandHistory.php';
require_once __DIR__ . '/core/patterns/CanvasState.php';
require_once __DIR__ . '/core/patterns/CanvasMemento.php';

require_once __DIR__ . '/core/services/ToolManager.php';
require_once __DIR__ . '/core/services/UndoRedoManager.php';
require_once __DIR__ . '/core/services/CanvasPersister.php';

require_once __DIR__ . '/tools/PencilTool.php';
require_once __DIR__ . '/tools/LineTool.php';
require_once __DIR__ . '/tools/RectangleTool.php';
require_once __DIR__ . '/tools/CircleTool.php';
require_once __DIR__ . '/tools/TextTool.php';
require_once __DIR__ . '/tools/FillTool.php';
require_once __DIR__ . '/tools/EraserTool.php';
require_once __DIR__ . '/tools/GradientTool.php';
require_once __DIR__ . '/tools/BezierTool.php';
require_once __DIR__ . '/tools/BrushTool.php';
require_once __DIR__ . '/tools/ColorInterpolator.php';

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? '';
        $response = ['success' => false, 'message' => 'Unknown action'];

        session_start();

        if (!isset($_SESSION['canvasState'])) {
            $_SESSION['canvasState'] = new CanvasState(800, 600);
        }

        if (!isset($_SESSION['toolManager'])) {
            $_SESSION['toolManager'] = new ToolManager();
        }

        if (!isset($_SESSION['drawingStrategy'])) {
            $_SESSION['drawingStrategy'] = new DrawingStrategy();
        }

        if (!isset($_SESSION['undoRedoManager'])) {
            $_SESSION['undoRedoManager'] = new UndoRedoManager();
        }

        if (!isset($_SESSION['canvasPersister'])) {
            $_SESSION['canvasPersister'] = new CanvasPersister();
        }

        /** @var CanvasState $canvasState */
        $canvasState = $_SESSION['canvasState'];
        /** @var ToolManager $toolManager */
        $toolManager = $_SESSION['toolManager'];
        /** @var DrawingStrategy $drawingStrategy */
        $drawingStrategy = $_SESSION['drawingStrategy'];
        /** @var UndoRedoManager $undoRedoManager */
        $undoRedoManager = $_SESSION['undoRedoManager'];
        /** @var CanvasPersister $canvasPersister */
        $canvasPersister = $_SESSION['canvasPersister'];

        $currentTool = $drawingStrategy->getCurrentTool();
        if (!$currentTool) {
            $toolManager->setActiveTool('Pencil');
            $drawingStrategy->setTool($toolManager->getActiveTool());
            $currentTool = $drawingStrategy->getCurrentTool();
        }

        switch ($action) {
            case 'init':
                $response = [
                    'success' => true,
                    'tools' => array_map(function ($tool) {
                        return [
                            'name' => $tool->getName(),
                            'icon' => $tool->getIcon()
                        ];
                    }, $toolManager->getTools()),
                    'currentTool' => $currentTool->getName(),
                    'settings' => $toolManager->getToolSettings()
                ];
                break;

            case 'setTool':
                $toolName = $_POST['tool'] ?? '';
                if ($toolManager->setActiveTool($toolName)) {
                    $drawingStrategy->setTool($toolManager->getActiveTool());
                    $currentTool = $drawingStrategy->getCurrentTool();

                    $settings = $toolManager->getToolSettings();
                    applySettingsToTool($currentTool, $settings);

                    $response = [
                        'success' => true,
                        'currentTool' => $currentTool->getName(),
                        'cursor' => $currentTool->getCursor()
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Tool not found'];
                }
                break;

            case 'updateSetting':
                $key = $_POST['key'] ?? '';
                $value = $_POST['value'] ?? '';

                if ($key && $value !== '') {
                    $toolManager->updateToolSetting($key, $value);

                    $settings = $toolManager->getToolSettings();
                    $this->applySettingsToTool($currentTool, $settings);

                    $response = ['success' => true];
                } else {
                    $response = ['success' => false, 'message' => 'Invalid setting'];
                }
                break;

            case 'startDrawing':
                $x = $_POST['x'] ?? 0;
                $y = $_POST['y'] ?? 0;

                $drawingStrategy->startDrawing($x, $y);

                $canvasPersister->saveState($canvasState->getImage());

                $response = ['success' => true];
                break;

            case 'continueDrawing':
                $x = $_POST['x'] ?? 0;
                $y = $_POST['y'] ?? 0;

                $drawingStrategy->continueDrawing($x, $y);

                $tempImage = imagecreatetruecolor($canvasState->getWidth(), $canvasState->getHeight());
                imagecopy($tempImage, $canvasState->getImage(), 0, 0, 0, 0, $canvasState->getWidth(), $canvasState->getHeight());
                $tempImage = $drawingStrategy->draw($tempImage);

                $tempFile = tempnam(sys_get_temp_dir(), 'paint');
                imagepng($tempImage, $tempFile);
                imagedestroy($tempImage);

                $response = [
                    'success' => true,
                    'preview' => base64_encode(file_get_contents($tempFile)),
                    'requiresRedraw' => $currentTool->requiresCanvasRedraw()
                ];

                unlink($tempFile);
                break;

            case 'endDrawing':
                $x = $_POST['x'] ?? 0;
                $y = $_POST['y'] ?? 0;

                if ($drawingStrategy->endDrawing($x, $y)) {

                    $command = new DrawingCommand(
                        $canvasState->getImage(),
                        $drawingStrategy,
                        $currentTool->getName()
                    );

                    $newImage = $undoRedoManager->executeCommand($command);
                    $canvasState->setImage($newImage);

                    $tempFile = tempnam(sys_get_temp_dir(), 'paint');
                    imagepng($newImage, $tempFile);

                    $response = [
                        'success' => true,
                        'image' => base64_encode(file_get_contents($tempFile))
                    ];

                    unlink($tempFile);
                } else {
                    $response = ['success' => false];
                }
                break;

            case 'undo':
                $image = $undoRedoManager->undo();
                if ($image) {
                    $canvasState->setImage($image);

                    $tempFile = tempnam(sys_get_temp_dir(), 'paint');
                    imagepng($image, $tempFile);

                    $response = [
                        'success' => true,
                        'image' => base64_encode(file_get_contents($tempFile)),
                        'canUndo' => $undoRedoManager->canUndo(),
                        'canRedo' => $undoRedoManager->canRedo()
                    ];

                    unlink($tempFile);
                } else {
                    $response = ['success' => false];
                }
                break;

            case 'redo':
                $image = $undoRedoManager->redo();
                if ($image) {
                    $canvasState->setImage($image);

                    $tempFile = tempnam(sys_get_temp_dir(), 'paint');
                    imagepng($image, $tempFile);

                    $response = [
                        'success' => true,
                        'image' => base64_encode(file_get_contents($tempFile)),
                        'canUndo' => $undoRedoManager->canUndo(),
                        'canRedo' => $undoRedoManager->canRedo()
                    ];

                    unlink($tempFile);
                } else {
                    $response = ['success' => false];
                }
                break;

            case 'clear':
                $canvasState->clear();
                $undoRedoManager->clearHistory();

                $tempFile = tempnam(sys_get_temp_dir(), 'paint');
                imagepng($canvasState->getImage(), $tempFile);

                $response = [
                    'success' => true,
                    'image' => base64_encode(file_get_contents($tempFile))
                ];

                unlink($tempFile);
                break;

            case 'save':
                $filename = $_POST['filename'] ?? 'drawing_' . date('Ymd_His') . '.png';
                $savedPath = $canvasPersister->saveImage($canvasState->getImage(), $filename);

                $response = [
                    'success' => true,
                    'path' => $savedPath,
                    'filename' => $filename
                ];
                break;

            case 'resize':
                $width = intval($_POST['width'] ?? $canvasState->getWidth());
                $height = intval($_POST['height'] ?? $canvasState->getHeight());

                if ($width > 0 && $height > 0) {
                    $canvasState->resize($width, $height);

                    $tempFile = tempnam(sys_get_temp_dir(), 'paint');
                    imagepng($canvasState->getImage(), $tempFile);

                    $response = [
                        'success' => true,
                        'image' => base64_encode(file_get_contents($tempFile))
                    ];

                    unlink($tempFile);
                } else {
                    $response = ['success' => false, 'message' => 'Invalid dimensions'];
                }
                break;

            default:
                $response = ['success' => false, 'message' => 'Invalid action'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}

function applySettingsToTool($tool, $settings)
{
    if (method_exists($tool, 'setColor')) {
        $tool->setColor($settings['color']);
    }

    if (method_exists($tool, 'setSize')) {
        $tool->setSize($settings['size']);
    }

    if (method_exists($tool, 'setOpacity')) {
        $tool->setOpacity($settings['opacity']);
    }

    if (method_exists($tool, 'setFillColor')) {
        $tool->setFillColor($settings['fillColor']);
    }

    if (method_exists($tool, 'setIsFilled')) {
        $tool->setIsFilled($settings['isFilled'] ?? false);
    }

    if (method_exists($tool, 'setStartColor')) {
        $tool->setStartColor($settings['gradientStart']);
    }

    if (method_exists($tool, 'setEndColor')) {
        $tool->setEndColor($settings['gradientEnd']);
    }

    if (method_exists($tool, 'setGradientType')) {
        $tool->setGradientType($settings['gradientType']);
    }

    if (method_exists($tool, 'setText')) {
        $tool->setText($settings['text']);
    }

    if (method_exists($tool, 'setFontSize')) {
        $tool->setFontSize($settings['fontSize']);
    }

    if (method_exists($tool, 'setFontFamily')) {
        $tool->setFontFamily($settings['fontFamily']);
    }

    if (method_exists($tool, 'setBrushType')) {
        $tool->setBrushType($settings['brushType'] ?? 'round');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paint Project</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:,">
    <style>
        #paint-canvas {
            background-color: #fff;
            border: 1px solid #ccc;
        }

        .canvas-error {
            color: red;
            padding: 20px;
            border: 2px solid red;
        }
    </style>

    <script>
        window.addEventListener('load', function() {
            if (!document.getElementById('paint-canvas')) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'canvas-error';
                errorDiv.innerHTML = 'ПОМИЛКА: Елемент &lt;canvas&gt; не знайдено. Перевірте HTML-код.';
                document.body.prepend(errorDiv);
            }
        });
    </script>
</head>

<body>
    <div class="paint-app">
        <div class="toolbar">
            <div class="tools" id="tools-container">
                <button class="tool-btn active" data-tool="Pencil" title="Pencil (P)">✏️ Pencil</button>
                <button class="tool-btn" data-tool="Line" title="Line (L)">─ Line</button>
                <button class="tool-btn" data-tool="Rectangle" title="Rectangle (R)">□ Rectangle</button>
                <button class="tool-btn" data-tool="Circle" title="Circle (C)">○ Circle</button>
                <button class="tool-btn" data-tool="Text" title="Text (T)">T Text</button>
                <button class="tool-btn" data-tool="Eraser" title="Eraser (E)">🧽 Eraser</button>
                <button class="tool-btn" data-tool="Gradient" title="Gradient (G)">🌈 Gradient</button>
                <button class="tool-btn" data-tool="Bezier" title="Bezier Curve (B)">⤴️ Bezier</button>
                <button class="tool-btn" data-tool="Brush" title="Brush (W)">🖌️ Brush</button>
            </div>

            <div class="canvas-container">
                <canvas id="paint-canvas" width="800" height="600"></canvas>
            </div>

            <div class="tool-settings" id="tool-settings">

                <div class="tool-settings-group" data-tool="Pencil">
                    <div class="setting-group">
                        <label for="pencil-color">Color:</label>
                        <input type="color" id="pencil-color" value="#000000" data-setting="color">
                        <div class="color-preview" style="background-color: #000000;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="pencil-size">Size:</label>
                        <input type="range" id="pencil-size" min="1" max="50" value="3" data-setting="size">
                        <span>3</span>
                    </div>
                    <div class="setting-group">
                        <label for="pencil-opacity">Opacity:</label>
                        <input type="range" id="pencil-opacity" min="1" max="100" value="100" data-setting="opacity">
                        <span>100%</span>
                    </div>
                </div>

                <div class="tool-settings-group" data-tool="Line">
                    <div class="setting-group">
                        <label for="line-color">Color:</label>
                        <input type="color" id="line-color" value="#000000" data-setting="color">
                        <div class="color-preview" style="background-color: #000000;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="line-size">Size:</label>
                        <input type="range" id="line-size" min="1" max="50" value="3" data-setting="size">
                        <span>3</span>
                    </div>
                    <div class="setting-group">
                        <label for="line-opacity">Opacity:</label>
                        <input type="range" id="line-opacity" min="1" max="100" value="100" data-setting="opacity">
                        <span>100%</span>
                    </div>
                </div>

                <div class="tool-settings-group" data-tool="Rectangle">
                    <div class="setting-group">
                        <label for="rect-color">Color:</label>
                        <input type="color" id="rect-color" value="#000000" data-setting="color">
                        <div class="color-preview" style="background-color: #000000;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="rect-size">Border Size:</label>
                        <input type="range" id="rect-size" min="1" max="50" value="2" data-setting="size">
                        <span>2</span>
                    </div>
                    <div class="setting-group">
                        <input type="checkbox" id="rect-filled" data-setting="isFilled">
                        <label for="rect-filled">Filled</label>
                    </div>
                    <div class="setting-group">
                        <label for="rect-fill-color">Fill Color:</label>
                        <input type="color" id="rect-fill-color" value="#ffffff" data-setting="fillColor">
                        <div class="color-preview" style="background-color: #ffffff;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="rect-opacity">Opacity:</label>
                        <input type="range" id="rect-opacity" min="1" max="100" value="100" data-setting="opacity">
                        <span>100%</span>
                    </div>
                </div>

                <div class="tool-settings-group" data-tool="Circle">
                    <div class="setting-group">
                        <label for="circle-color">Color:</label>
                        <input type="color" id="circle-color" value="#000000" data-setting="color">
                        <div class="color-preview" style="background-color: #000000;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="circle-size">Border Size:</label>
                        <input type="range" id="circle-size" min="1" max="50" value="2" data-setting="size">
                        <span>2</span>
                    </div>
                    <div class="setting-group">
                        <input type="checkbox" id="circle-filled" data-setting="isFilled">
                        <label for="circle-filled">Filled</label>
                    </div>
                    <div class="setting-group">
                        <label for="circle-fill-color">Fill Color:</label>
                        <input type="color" id="circle-fill-color" value="#ffffff" data-setting="fillColor">
                        <div class="color-preview" style="background-color: #ffffff;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="circle-opacity">Opacity:</label>
                        <input type="range" id="circle-opacity" min="1" max="100" value="100" data-setting="opacity">
                        <span>100%</span>
                    </div>
                </div>

                <div class="tool-settings-group" data-tool="Text">
                    <div class="setting-group">
                        <label for="text-color">Color:</label>
                        <input type="color" id="text-color" value="#000000" data-setting="color">
                        <div class="color-preview" style="background-color: #000000;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="text-content">Text:</label>
                        <input type="text" id="text-content" value="Sample Text" data-setting="text">
                    </div>
                    <div class="setting-group">
                        <label for="text-font-size">Font Size:</label>
                        <input type="range" id="text-font-size" min="8" max="72" value="16" data-setting="fontSize">
                        <span>16</span>
                    </div>
                    <div class="setting-group">
                        <label for="text-font-family">Font:</label>
                        <select id="text-font-family" data-setting="fontFamily">
                            <option value="Arial">Arial</option>
                            <option value="Times">Times New Roman</option>
                            <option value="Courier">Courier New</option>
                        </select>
                    </div>
                    <div class="setting-group">
                        <label for="text-opacity">Opacity:</label>
                        <input type="range" id="text-opacity" min="1" max="100" value="100" data-setting="opacity">
                        <span>100%</span>
                    </div>
                </div>

                <div class="tool-settings-group" data-tool="Fill">
                    <div class="setting-group">
                        <label for="fill-color">Color:</label>
                        <input type="color" id="fill-color" value="#ff0000" data-setting="color">
                        <div class="color-preview" style="background-color: #ff0000;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="fill-opacity">Opacity:</label>
                        <input type="range" id="fill-opacity" min="1" max="100" value="100" data-setting="opacity">
                        <span>100%</span>
                    </div>
                </div>

                <div class="tool-settings-group" data-tool="Eraser">
                    <div class="setting-group">
                        <label for="eraser-size">Size:</label>
                        <input type="range" id="eraser-size" min="1" max="50" value="10" data-setting="size">
                        <span>10</span>
                    </div>
                </div>

                <div class="tool-settings-group" data-tool="Gradient">
                    <div class="setting-group">
                        <label for="gradient-start">Start Color:</label>
                        <input type="color" id="gradient-start" value="#000000" data-setting="gradientStart">
                        <div class="color-preview" style="background-color: #000000;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="gradient-end">End Color:</label>
                        <input type="color" id="gradient-end" value="#ffffff" data-setting="gradientEnd">
                        <div class="color-preview" style="background-color: #ffffff;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="gradient-type">Type:</label>
                        <select id="gradient-type" data-setting="gradientType">
                            <option value="linear">Linear</option>
                            <option value="radial">Radial</option>
                        </select>
                    </div>
                    <div class="setting-group">
                        <label for="gradient-opacity">Opacity:</label>
                        <input type="range" id="gradient-opacity" min="1" max="100" value="100" data-setting="opacity">
                        <span>100%</span>
                    </div>
                </div>

                <div class="tool-settings-group" data-tool="Bezier">
                    <div class="setting-group">
                        <label for="bezier-color">Color:</label>
                        <input type="color" id="bezier-color" value="#000000" data-setting="color">
                        <div class="color-preview" style="background-color: #000000;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="bezier-size">Size:</label>
                        <input type="range" id="bezier-size" min="1" max="50" value="3" data-setting="size">
                        <span>3</span>
                    </div>
                    <div class="setting-group">
                        <label for="bezier-opacity">Opacity:</label>
                        <input type="range" id="bezier-opacity" min="1" max="100" value="100" data-setting="opacity">
                        <span>100%</span>
                    </div>
                </div>

                <div class="tool-settings-group" data-tool="Brush">
                    <div class="setting-group">
                        <label for="brush-color">Color:</label>
                        <input type="color" id="brush-color" value="#000000" data-setting="color">
                        <div class="color-preview" style="background-color: #000000;"></div>
                    </div>
                    <div class="setting-group">
                        <label for="brush-size">Size:</label>
                        <input type="range" id="brush-size" min="1" max="50" value="5" data-setting="size">
                        <span>5</span>
                    </div>
                    <div class="setting-group">
                        <label for="brush-type">Type:</label>
                        <select id="brush-type" data-setting="brushType">
                            <option value="round">Round</option>
                            <option value="square">Square</option>
                        </select>
                    </div>
                    <div class="setting-group">
                        <label for="brush-opacity">Opacity:</label>
                        <input type="range" id="brush-opacity" min="1" max="100" value="100" data-setting="opacity">
                        <span>100%</span>
                    </div>

                </div>
                <div class="setting-group">
                    <label for="brush-opacity">Opacity:</label>
                    <input type="range" id="brush-opacity" min="1" max="100" value="100" data-setting="opacity">
                    <span>100%</span>
                </div>
            </div>
        </div>

        <div class="actions">
            <button id="undo-btn" title="Undo (Ctrl+Z)">↩️ Undo</button>
            <button id="clear-btn" title="Clear Canvas">🧹 Clear</button>
            <button id="save-btn" title="Save Image">💾 Save</button>
        </div>
    </div>

    <script src="assets/js/script.js"></script>

</body>

</html>