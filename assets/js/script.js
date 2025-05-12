class PaintApp {
    constructor() {
        this.canvas = document.getElementById("paint-canvas");
        this.ctx = this.canvas.getContext("2d");
        this.isDrawing = false;

        this.toolService = new ToolService();
        this.currentTool = "pencil";
        this.color = "#000000";
        this.brushSize = 5;

        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 20;

        this.initCanvas();
        this.setupEventListeners();
    }

    initCanvas() {
        this.ctx.fillStyle = "white";
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        this.saveState();
    }

    setupEventListeners() {
        this.canvas.addEventListener("mousedown", this.startDrawing.bind(this));
        this.canvas.addEventListener("mousemove", this.draw.bind(this));
        this.canvas.addEventListener("mouseup", this.stopDrawing.bind(this));
        this.canvas.addEventListener("mouseout", this.stopDrawing.bind(this));

        document.addEventListener("keydown", (e) => {
            if (e.ctrlKey && e.key === 'z') {
                this.undo();
            }
        });
    }

    startDrawing(e) {
        this.isDrawing = true;
        this.ctx.beginPath();
        this.ctx.moveTo(...this.getMousePos(e));
    }

    draw(e) {
        if (!this.isDrawing) return;

        const [x, y] = this.getMousePos(e);

        this.ctx.lineWidth = this.brushSize;
        this.ctx.lineCap = "round";
        this.ctx.strokeStyle = this.color;

        const tool = this.toolService.getTool(this.currentTool);
        tool.draw(this.ctx, x, y);
    }

    stopDrawing() {
        if (this.isDrawing) {
            this.isDrawing = false;
            this.ctx.beginPath();
            this.saveState();
        }
    }

    getMousePos(e) {
        const rect = this.canvas.getBoundingClientRect();
        return [e.clientX - rect.left, e.clientY - rect.top];
    }

    saveState() {
        if (this.historyIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.historyIndex + 1);
        }

        const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
        this.history.push(imageData);
        this.historyIndex++;

        if (this.history.length > this.maxHistory) {
            this.history.shift();
            this.historyIndex--;
        }
    }

    undo() {
        if (this.historyIndex <= 0) {
            this.clearCanvas();
            return;
        }

        this.historyIndex--;
        this.ctx.putImageData(this.history[this.historyIndex], 0, 0);
    }

    clearCanvas() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.ctx.fillStyle = "white";
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
    }

    selectTool(toolName) {
        this.currentTool = toolName;
        const tool = this.toolService.getTool(toolName);
        this.canvas.style.cursor = tool.cursor;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const app = new PaintApp();

});