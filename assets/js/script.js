class PaintApp {
  constructor() {
    this.canvas = document.getElementById("paint-canvas");
    if (!this.canvas) throw new Error("Canvas element not found");
    this.ctx = this.canvas.getContext("2d");
    if (!this.ctx) throw new Error("Canvas context not available");

    this.toolsContainer = document.getElementById("tools-container");
    this.toolSettings = document.getElementById("tool-settings");
    this.undoBtn = document.getElementById("undo-btn");
    this.redoBtn = document.getElementById("redo-btn");
    this.clearBtn = document.getElementById("clear-btn");
    this.saveBtn = document.getElementById("save-btn");
    this.resizeBtn = document.getElementById("resize-btn");
    this.canvasWidth = document.getElementById("canvas-width");
    this.canvasHeight = document.getElementById("canvas-height");

    if (!this.undoBtn || !this.redoBtn || !this.clearBtn || !this.saveBtn) {
      console.warn("Some buttons not found, functionality will be limited");
    }

    this.history = [];
    this.historyIndex = -1;
    this.maxHistorySteps = 20;
    this.fillShape = "Rectangle";
    this.isDrawing = false;
    this.currentTool = "Pencil";
    this.toolSettingsConfig = {
      color: "#000000",
      size: 5,
      opacity: 100,
      fillColor: "#ffffff",
      isFilled: false,
      gradientStart: "#000000",
      gradientEnd: "#ffffff",
      gradientType: "linear",
      text: "Ваш текст",
      fontSize: 16,
      fontFamily: "Arial",
      brushType: "round",
    };

    this.lastX = 0;
    this.lastY = 0;
    this.textInputActive = false;
    this.tempCanvas = document.createElement("canvas");
    this.tempCtx = this.tempCanvas.getContext("2d");

    this.initCanvas();
    this.initTools();
    this.setupEventListeners();
  }

  initCanvas() {
    this.tempCanvas.width = this.canvas.width;
    this.tempCanvas.height = this.canvas.height;
    this.ctx.fillStyle = "white";
    this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
    this.tempCtx.fillStyle = "white";
    this.tempCtx.fillRect(0, 0, this.canvas.width, this.canvas.height);
    this.ctx.lineJoin = "round";
    this.ctx.lineCap = "round";
    this.tempCtx.lineJoin = "round";
    this.tempCtx.lineCap = "round";
  }

  initTools() {
    this.renderTools();
    this.selectTool("Pencil");
  }

  renderTools() {
    this.toolsContainer.innerHTML = "";
    const tools = [
      { name: "Pencil", icon: "✏️" },
      { name: "Line", icon: "─" },
      { name: "Rectangle", icon: "□" },
      { name: "Circle", icon: "○" },
      { name: "Text", icon: "T" },
      { name: "Eraser", icon: "🧽" },
      { name: "Gradient", icon: "🌈" },
      { name: "Brush", icon: "🖌️" },
    ];

    tools.forEach((tool) => {
      const toolBtn = document.createElement("button");
      toolBtn.className = `tool-btn ${
        tool.name === this.currentTool ? "active" : ""
      }`;
      toolBtn.innerHTML = `${tool.icon} ${tool.name}`;
      toolBtn.dataset.tool = tool.name;
      toolBtn.addEventListener("click", () => this.selectTool(tool.name));
      this.toolsContainer.appendChild(toolBtn);
    });
  }

  selectTool(toolName) {
    if (this.textInputActive) return;

    document
      .querySelectorAll(".tool-btn")
      .forEach((btn) => btn.classList.remove("active"));
    document
      .querySelector(`.tool-btn[data-tool="${toolName}"]`)
      .classList.add("active");
    this.currentTool = toolName;
    this.renderToolSettings();

    const cursors = {
      Pencil: "crosshair",
      Line: "crosshair",
      Rectangle: "crosshair",
      Circle: "crosshair",
      Text: "text",
      Fill: "crosshair",
      Eraser: "crosshair",
      Gradient: "crosshair",
      Brush: "crosshair",
    };
    this.canvas.style.cursor = cursors[toolName] || "default";
  }

  renderToolSettings() {
    this.toolSettings.innerHTML = "";

    const settingsMap = {
      Pencil: ["color", "size", "opacity"],
      Line: ["color", "size", "opacity"],
      Rectangle: ["color", "size", "isFilled", "fillColor", "opacity"],
      Circle: ["color", "size", "isFilled", "fillColor", "opacity"],
      Text: ["color", "text", "fontSize", "fontFamily", "opacity"],
      Fill: ["color", "opacity", "fillMethod"],
      Eraser: ["size"],
      Gradient: ["gradientStart", "gradientEnd", "gradientType", "opacity"],
      Brush: ["color", "size", "brushType", "opacity"],
    };

    settingsMap[this.currentTool]?.forEach((setting) => {
      switch (setting) {
        case "color":
        case "fillColor":
        case "gradientStart":
        case "gradientEnd":
          this.addColorSetting(
            setting,
            setting.replace(/([A-Z])/g, " $1"),
            this.toolSettingsConfig[setting]
          );
          break;
        case "tolerance":
          this.addRangeSetting(
            setting,
            "Color Tolerance",
            this.toolSettingsConfig[setting] || 0,
            0,
            100,
            1
          );
          break;
        case "size":
        case "fontSize":
        case "opacity":
          this.addRangeSetting(
            setting,
            setting.replace(/([A-Z])/g, " $1"),
            this.toolSettingsConfig[setting],
            setting === "opacity" ? 1 : 1,
            setting === "fontSize" ? 72 : 50
          );
          break;
        case "isFilled":
          this.addCheckboxSetting(
            setting,
            "Filled",
            this.toolSettingsConfig[setting]
          );
          break;
        case "fillMethod":
          this.addSelectSetting(
            setting,
            "Detection Method",
            [
              { value: "auto", label: "Auto-detect shape" },
              { value: "force-rect", label: "Force rectangle" },
              { value: "force-circle", label: "Force circle" },
            ],
            this.toolSettingsConfig[setting] || "auto"
          );
          break;
        case "gradientType":
          this.addSelectSetting(
            setting,
            "Type",
            [
              { value: "linear", label: "Linear" },
              { value: "radial", label: "Radial" },
            ],
            this.toolSettingsConfig[setting]
          );
          break;
        case "brushType":
          this.addSelectSetting(
            setting,
            "Brush Type",
            [
              { value: "round", label: "Round" },
              { value: "square", label: "Square" },
            ],
            this.toolSettingsConfig[setting]
          );
          break;
        case "fontFamily":
          this.addSelectSetting(
            setting,
            "Font",
            [
              { value: "Arial", label: "Arial" },
              { value: "Times", label: "Times New Roman" },
              { value: "Courier", label: "Courier New" },
            ],
            this.toolSettingsConfig[setting]
          );
          break;
        case "text":
          this.addTextSetting(
            setting,
            "Text",
            this.toolSettingsConfig[setting]
          );
          break;
      }
    });
  }

  floodFill(ctx, x, y, fillColor, tolerance = 0) {
    const canvas = ctx.canvas;
    const width = canvas.width;
    const height = canvas.height;

    const imageData = ctx.getImageData(0, 0, width, height);
    const pixels = imageData.data;

    const clickedPos = (y * width + x) * 4;
    const targetR = pixels[clickedPos];
    const targetG = pixels[clickedPos + 1];
    const targetB = pixels[clickedPos + 2];
    const targetA = pixels[clickedPos + 3];

    if (
      Math.abs(fillColor.r - targetR) <= tolerance &&
      Math.abs(fillColor.g - targetG) <= tolerance &&
      Math.abs(fillColor.b - targetB) <= tolerance &&
      Math.abs(fillColor.a - targetA) <= tolerance
    ) {
      return;
    }

    const queue = [];
    queue.push({ x, y });

    while (queue.length > 0) {
      const { x: currentX, y: currentY } = queue.shift();
      const pos = (currentY * width + currentX) * 4;

      if (
        currentX < 0 ||
        currentX >= width ||
        currentY < 0 ||
        currentY >= height
      ) {
        continue;
      }

      const r = pixels[pos];
      const g = pixels[pos + 1];
      const b = pixels[pos + 2];
      const a = pixels[pos + 3];

      if (
        Math.abs(r - targetR) <= tolerance &&
        Math.abs(g - targetG) <= tolerance &&
        Math.abs(b - targetB) <= tolerance &&
        Math.abs(a - targetA) <= tolerance
      ) {
        pixels[pos] = fillColor.r;
        pixels[pos + 1] = fillColor.g;
        pixels[pos + 2] = fillColor.b;
        pixels[pos + 3] = fillColor.a !== undefined ? fillColor.a : 255;

        queue.push({ x: currentX + 1, y: currentY });
        queue.push({ x: currentX - 1, y: currentY });
        queue.push({ x: currentX, y: currentY + 1 });
        queue.push({ x: currentX, y: currentY - 1 });
      }
    }

    ctx.putImageData(imageData, 0, 0);
  }

  fillArea(ctx, x, y, width, height, fillColor, fillMethod = "auto") {
    ctx.fillStyle = fillColor;

    let shape = fillMethod;

    if (fillMethod === "auto") {
      const ratio = width / height;

      if (Math.abs(ratio - 1) < 0.1) {
        shape = "circle";
      } else {
        shape = "rect";
      }
    }

    if (shape === "force-rect" || shape === "rect") {
      ctx.fillRect(x, y, width, height);
    } else if (shape === "force-circle" || shape === "circle") {
      ctx.beginPath();
      ctx.arc(
        x + width / 2,
        y + height / 2,
        Math.min(width, height) / 2,
        0,
        2 * Math.PI
      );
      ctx.fill();
    }
  }

  addColorSetting(name, label, value) {
    const group = document.createElement("div");
    group.className = "setting-group";

    const input = document.createElement("input");
    input.type = "color";
    input.id = `setting-${name}`;
    input.value = value;
    input.dataset.setting = name;
    input.addEventListener("input", (e) => {
      this.toolSettingsConfig[name] = e.target.value;
      document.querySelector(
        `.color-preview[data-setting="${name}"]`
      ).style.backgroundColor = e.target.value;
    });

    group.innerHTML = `
      <label for="setting-${name}">${label}</label>
      <div class="color-preview" data-setting="${name}" style="background-color: ${value}"></div>
    `;
    group.appendChild(input);
    this.toolSettings.appendChild(group);
  }

  addRangeSetting(name, label, value, min, max) {
    const group = document.createElement("div");
    group.className = "setting-group";
    group.innerHTML = `
      <label for="setting-${name}">${label}</label>
      <span>${value}</span>
    `;

    const input = document.createElement("input");
    input.type = "range";
    input.id = `setting-${name}`;
    input.min = min;
    input.max = max;
    input.value = value;
    input.dataset.setting = name;
    input.addEventListener("input", (e) => {
      this.toolSettingsConfig[name] = e.target.value;
      e.target.nextElementSibling.textContent = e.target.value;
    });

    group.insertBefore(input, group.querySelector("span"));
    this.toolSettings.appendChild(group);
  }

  addSelectSetting(name, label, options, selectedValue) {
    const group = document.createElement("div");
    group.className = "setting-group";
    group.innerHTML = `<label>${label}</label>`;

    const select = document.createElement("select");
    select.dataset.setting = name;
    options.forEach((option) => {
      const optionEl = document.createElement("option");
      optionEl.value = option.value;
      optionEl.textContent = option.label;
      optionEl.selected = option.value === selectedValue;
      select.appendChild(optionEl);
    });
    select.addEventListener("change", (e) => {
      this.toolSettingsConfig[name] = e.target.value;
    });

    group.appendChild(select);
    this.toolSettings.appendChild(group);
  }

  addCheckboxSetting(name, label, checked) {
    const group = document.createElement("div");
    group.className = "setting-group";

    const input = document.createElement("input");
    input.type = "checkbox";
    input.id = `setting-${name}`;
    input.checked = checked;
    input.dataset.setting = name;
    input.addEventListener("change", (e) => {
      this.toolSettingsConfig[name] = e.target.checked;
    });

    group.appendChild(input);
    group.appendChild(document.createElement("label")).textContent = label;
    group.querySelector("label").htmlFor = `setting-${name}`;
    this.toolSettings.appendChild(group);
  }

  addTextSetting(name, label, value) {
    const group = document.createElement("div");
    group.className = "setting-group";
    group.innerHTML = `<label for="setting-${name}">${label}</label>`;

    const input = document.createElement("input");
    input.type = "text";
    input.id = `setting-${name}`;
    input.value = value;
    input.dataset.setting = name;
    input.addEventListener("input", (e) => {
      this.toolSettingsConfig[name] = e.target.value;
    });

    group.appendChild(input);
    this.toolSettings.appendChild(group);
  }

  setupEventListeners() {
    if (this.canvas) {
      this.canvas.addEventListener("mousedown", (e) => this.startDrawing(e));
      this.canvas.addEventListener("mousemove", (e) => this.draw(e));
      this.canvas.addEventListener("mouseup", () => this.stopDrawing());
      this.canvas.addEventListener("mouseout", () => this.stopDrawing());

      this.canvas.addEventListener("touchstart", (e) => {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent("mousedown", {
          clientX: touch.clientX,
          clientY: touch.clientY,
        });
        this.canvas.dispatchEvent(mouseEvent);
      });

      this.canvas.addEventListener("touchmove", (e) => {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent("mousemove", {
          clientX: touch.clientX,
          clientY: touch.clientY,
        });
        this.canvas.dispatchEvent(mouseEvent);
      });

      this.canvas.addEventListener("touchend", () => {
        const mouseEvent = new MouseEvent("mouseup");
        this.canvas.dispatchEvent(mouseEvent);
      });
    }

    if (this.undoBtn) {
      this.undoBtn.addEventListener("click", () => this.undo());
    }
    if (this.redoBtn) {
      this.redoBtn.addEventListener("click", () => this.redo());
    }
    if (this.clearBtn) {
      this.clearBtn.addEventListener("click", () => this.clearCanvas());
    }
    if (this.saveBtn) {
      this.saveBtn.addEventListener("click", () => this.saveCanvas());
    }
    if (this.resizeBtn) {
      this.resizeBtn.addEventListener("click", () => this.resizeCanvas());
    }

    document.addEventListener("keydown", (e) => {
      if (e.ctrlKey && e.key === "z") {
        e.preventDefault();
        this.undo();
      } else if (e.ctrlKey && e.key === "y") {
        e.preventDefault();
        this.redo();
      } else if (e.ctrlKey && e.key === "s") {
        e.preventDefault();
        this.saveCanvas();
      }
    });
  }

  getCanvasCoordinates(e) {
    const rect = this.canvas.getBoundingClientRect();
    return {
      x: (e.clientX - rect.left) * (this.canvas.width / rect.width),
      y: (e.clientY - rect.top) * (this.canvas.height / rect.height),
    };
  }

  startDrawing(e) {
    if (this.currentTool === "Text") {
      this.startTextInput(e);
      return;
    }

    this.isDrawing = true;
    const pos = this.getCanvasCoordinates(e);
    [this.lastX, this.lastY] = [pos.x, pos.y];

    this.tempCtx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    this.tempCtx.drawImage(this.canvas, 0, 0);

    if (this.currentTool === "Fill") {
      if (["Rectangle", "Circle"].includes(this.fillShape)) {
        this.drawFilledShape(this.fillShape, pos.x, pos.y);
      } else {
        this.fillArea(pos.x, pos.y);
      }
      this.isDrawing = false;
    }
  }

  startTextInput(e) {
    const pos = this.getCanvasCoordinates(e);
    const text = prompt("Введіть текст:", this.toolSettingsConfig.text);
    if (text !== null) {
      this.toolSettingsConfig.text = text;
      this.drawText(pos.x, pos.y);
    }
  }

  draw(e) {
    if (
      !this.isDrawing ||
      this.currentTool === "Text" ||
      this.currentTool === "Fill"
    )
      return;

    const pos = this.getCanvasCoordinates(e);

    if (
      ["Line", "Rectangle", "Circle", "Gradient"].includes(this.currentTool)
    ) {
      this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
      this.ctx.drawImage(this.tempCanvas, 0, 0);
    }

    this.ctx.lineWidth = this.toolSettingsConfig.size;
    this.ctx.strokeStyle =
      this.currentTool === "Eraser" ? "white" : this.toolSettingsConfig.color;
    this.ctx.fillStyle = this.toolSettingsConfig.color;
    this.ctx.globalAlpha = this.toolSettingsConfig.opacity / 100;

    switch (this.currentTool) {
      case "Pencil":
        this.drawPencil(pos.x, pos.y);
        break;
      case "Brush":
        this.drawBrush(pos.x, pos.y);
        break;
      case "Line":
        this.drawLine(pos.x, pos.y);
        break;
      case "Rectangle":
        this.drawRectangle(pos.x, pos.y);
        break;
      case "Circle":
        this.drawCircle(pos.x, pos.y);
        break;
      case "Gradient":
        this.drawGradient(pos.x, pos.y);
        break;
      case "Eraser":
        this.drawEraser(pos.x, pos.y);
        break;
    }
  }

  drawPencil(x, y) {
    this.ctx.beginPath();
    this.ctx.moveTo(this.lastX, this.lastY);
    this.ctx.lineTo(x, y);
    this.ctx.stroke();
    [this.lastX, this.lastY] = [x, y];
  }

  drawBrush(x, y) {
    this.ctx.beginPath();
    if (this.toolSettingsConfig.brushType === "round") {
      this.ctx.arc(x, y, this.toolSettingsConfig.size / 2, 0, Math.PI * 2);
      this.ctx.fill();
    } else {
      this.ctx.rect(
        x - this.toolSettingsConfig.size / 2,
        y - this.toolSettingsConfig.size / 2,
        this.toolSettingsConfig.size,
        this.toolSettingsConfig.size
      );
      this.ctx.fill();
    }
    [this.lastX, this.lastY] = [x, y];
  }

  drawEraser(x, y) {
    this.ctx.globalCompositeOperation = "destination-out";
    this.ctx.beginPath();
    this.ctx.arc(x, y, this.toolSettingsConfig.size / 2, 0, Math.PI * 2);
    this.ctx.fill();
    this.ctx.globalCompositeOperation = "source-over";
    [this.lastX, this.lastY] = [x, y];
  }

  drawLine(x, y) {
    this.ctx.beginPath();
    this.ctx.moveTo(this.lastX, this.lastY);
    this.ctx.lineTo(x, y);
    this.ctx.stroke();
  }

  drawRectangle(x, y) {
    this.ctx.beginPath();
    this.ctx.rect(
      Math.min(this.lastX, x),
      Math.min(this.lastY, y),
      Math.abs(x - this.lastX),
      Math.abs(y - this.lastY)
    );
    if (this.toolSettingsConfig.isFilled) {
      this.ctx.fillStyle = this.toolSettingsConfig.fillColor;
      this.ctx.fill();
    }
    this.ctx.stroke();
  }

  drawCircle(x, y) {
    const radius = Math.sqrt(
      Math.pow(x - this.lastX, 2) + Math.pow(y - this.lastY, 2)
    );
    this.ctx.beginPath();
    this.ctx.arc(this.lastX, this.lastY, radius, 0, Math.PI * 2);
    if (this.toolSettingsConfig.isFilled) {
      this.ctx.fillStyle = this.toolSettingsConfig.fillColor;
      this.ctx.fill();
    }
    this.ctx.stroke();
  }

  drawText(x, y) {
    this.ctx.font = `${this.toolSettingsConfig.fontSize}px ${this.toolSettingsConfig.fontFamily}`;
    this.ctx.fillStyle = this.toolSettingsConfig.color;
    this.ctx.globalAlpha = this.toolSettingsConfig.opacity / 100;
    this.ctx.fillText(
      this.toolSettingsConfig.text,
      x,
      y + this.toolSettingsConfig.fontSize
    );
  }

  fillArea(x, y) {
    const imageData = this.ctx.getImageData(
      0,
      0,
      this.canvas.width,
      this.canvas.height
    );
    const pixels = imageData.data;
    const width = imageData.width;

    const startX = Math.floor(x);
    const startY = Math.floor(y);
    const startIndex = (startY * width + startX) * 4;

    const targetColor = pixels.slice(startIndex, startIndex + 4);
    const fillColor = this.hexToRgba(
      this.toolSettingsConfig.color,
      this.toolSettingsConfig.opacity / 100
    );

    if (this.colorsMatch(targetColor, fillColor)) return;

    const stack = [[startX, startY]];

    while (stack.length > 0) {
      const [curX, curY] = stack.pop();
      const idx = (curY * width + curX) * 4;

      const currentColor = pixels.slice(idx, idx + 4);

      if (this.colorsMatch(currentColor, targetColor)) {
        pixels[idx] = fillColor[0];
        pixels[idx + 1] = fillColor[1];
        pixels[idx + 2] = fillColor[2];
        pixels[idx + 3] = fillColor[3];

        if (curX > 0) stack.push([curX - 1, curY]);
        if (curX < width - 1) stack.push([curX + 1, curY]);
        if (curY > 0) stack.push([curX, curY - 1]);
        if (curY < imageData.height - 1) stack.push([curX, curY + 1]);
      }
    }

    this.ctx.putImageData(imageData, 0, 0);
  }

  hexToRgba(hex, alpha = 1) {
    const bigint = parseInt(hex.slice(1), 16);
    return [
      (bigint >> 16) & 255,
      (bigint >> 8) & 255,
      bigint & 255,
      Math.floor(alpha * 255),
    ];
  }

  drawFilledShape(shape, x, y) {
    const size = 100;
    this.ctx.fillStyle = this.toolSettingsConfig.color;
    this.ctx.globalAlpha = this.toolSettingsConfig.opacity / 100;

    if (shape === "Rectangle") {
      this.ctx.fillRect(x - size / 2, y - size / 2, size, size);
    } else if (shape === "Circle") {
      this.ctx.beginPath();
      this.ctx.arc(x, y, size / 2, 0, Math.PI * 2);
      this.ctx.fill();
    }
  }

  colorsMatch(a, b) {
    return a[0] === b[0] && a[1] === b[1] && a[2] === b[2] && a[3] === b[3];
  }

  getPixelColor(imageData, x, y) {
    const offset = (y * imageData.width + x) * 4;
    return {
      r: imageData.data[offset],
      g: imageData.data[offset + 1],
      b: imageData.data[offset + 2],
      a: imageData.data[offset + 3],
    };
  }

  colorsMatch(c1, c2) {
    return c1.r === c2.r && c1.g === c2.g && c1.b === c2.b && c1.a === c2.a;
  }

  hexToRgb(hex) {
    const r = parseInt(hex.substring(1, 3), 16);
    const g = parseInt(hex.substring(3, 5), 16);
    const b = parseInt(hex.substring(5, 7), 16);
    return { r, g, b, a: 255 };
  }

  floodFill(imageData, x, y, targetColor, fillColor) {
    const stack = [{ x, y }];
    const width = imageData.width;
    const height = imageData.height;
    const data = imageData.data;

    while (stack.length) {
      const { x, y } = stack.pop();
      const offset = (y * width + x) * 4;

      if (x < 0 || x >= width || y < 0 || y >= height) continue;

      const r = data[offset];
      const g = data[offset + 1];
      const b = data[offset + 2];
      const a = data[offset + 3];

      if (
        r === targetColor.r &&
        g === targetColor.g &&
        b === targetColor.b &&
        a === targetColor.a
      ) {
        data[offset] = fillColor.r;
        data[offset + 1] = fillColor.g;
        data[offset + 2] = fillColor.b;
        data[offset + 3] = fillColor.a;

        stack.push({ x: x + 1, y });
        stack.push({ x: x - 1, y });
        stack.push({ x, y: y + 1 });
        stack.push({ x, y: y - 1 });
      }
    }
  }

  drawGradient(x, y) {
    const gradient =
      this.toolSettingsConfig.gradientType === "linear"
        ? this.ctx.createLinearGradient(this.lastX, this.lastY, x, y)
        : this.ctx.createRadialGradient(
            this.lastX,
            this.lastY,
            5,
            this.lastX,
            this.lastY,
            Math.sqrt(Math.pow(x - this.lastX, 2) + Math.pow(y - this.lastY, 2))
          );

    gradient.addColorStop(0, this.toolSettingsConfig.gradientStart);
    gradient.addColorStop(1, this.toolSettingsConfig.gradientEnd);

    this.ctx.fillStyle = gradient;
    this.ctx.fillRect(
      Math.min(this.lastX, x),
      Math.min(this.lastY, y),
      Math.abs(x - this.lastX),
      Math.abs(y - this.lastY)
    );
  }

  stopDrawing() {
    if (!this.isDrawing) return;
    this.isDrawing = false;

    this.saveState();

    const imageData = this.ctx.getImageData(
      0,
      0,
      this.canvas.width,
      this.canvas.height
    );

    if (this.historyIndex < this.history.length - 1) {
      this.history = this.history.slice(0, this.historyIndex + 1);
    }

    this.history.push(imageData);
    this.historyIndex++;

    if (this.history.length > this.maxHistorySteps) {
      this.history.shift();
      this.historyIndex--;
    }

    if (
      this.currentTool !== "Pencil" &&
      this.currentTool !== "Brush" &&
      this.currentTool !== "Eraser"
    ) {
      this.tempCtx.clearRect(0, 0, this.canvas.width, this.canvas.height);
      this.tempCtx.drawImage(this.canvas, 0, 0);
    }
  }

  undo() {
    if (this.historyIndex <= 0) {
      this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
      this.ctx.fillStyle = "white";
      this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
      this.historyIndex = -1;
      return;
    }

    this.historyIndex--;
    const imageData = this.history[this.historyIndex];
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    this.ctx.putImageData(imageData, 0, 0);
  }

  redo() {
    if (this.historyIndex >= this.history.length - 1) return;

    this.historyIndex++;
    const imageData = this.history[this.historyIndex];
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    this.ctx.putImageData(imageData, 0, 0);
  }

  clearCanvas() {
    if (confirm("Очистити полотно?")) {
      this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
      this.ctx.fillStyle = "white";
      this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
      this.tempCtx.clearRect(0, 0, this.canvas.width, this.canvas.height);
      this.tempCtx.fillStyle = "white";
      this.tempCtx.fillRect(0, 0, this.canvas.width, this.canvas.height);

      this.history = [];
      this.historyIndex = -1;

      this.saveState();
    }
  }

  saveCanvas() {
    const link = document.createElement("a");
    link.download = `малюнок-${new Date().toISOString().slice(0, 10)}.png`;
    link.href = this.canvas.toDataURL("image/png");
    link.click();
  }

  resizeCanvas() {
    const width = parseInt(this.canvasWidth.value) || 800;
    const height = parseInt(this.canvasHeight.value) || 600;

    if (width < 100 || height < 100 || width > 2000 || height > 2000) {
      alert("Розміри повинні бути від 100 до 2000 пікселів");
      return;
    }

    const temp = document.createElement("canvas");
    temp.width = this.canvas.width;
    temp.height = this.canvas.height;
    temp.getContext("2d").drawImage(this.canvas, 0, 0);

    this.canvas.width = width;
    this.canvas.height = height;
    this.ctx.drawImage(temp, 0, 0, width, height);
  }

  saveState() {
    const imageData = this.ctx.getImageData(
      0,
      0,
      this.canvas.width,
      this.canvas.height
    );

    if (this.historyIndex < this.history.length - 1) {
      this.history = this.history.slice(0, this.historyIndex + 1);
    }

    this.history.push(imageData);
    this.historyIndex++;

    if (this.history.length > this.maxHistorySteps) {
      this.history.shift();
      this.historyIndex--;
    }
  }
}

document.addEventListener("DOMContentLoaded", () => {
  try {
    const paintApp = new PaintApp();

    setTimeout(() => {
      paintApp.saveState();
    }, 100);
  } catch (error) {
    console.error("Failed to initialize PaintApp:", error);
    alert("Помилка ініціалізації додатку. Перевірте консоль для деталей.");
  }
});
