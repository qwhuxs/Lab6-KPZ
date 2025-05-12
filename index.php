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

            <script src="assets/js/script.js"></script>
</body>

</html>