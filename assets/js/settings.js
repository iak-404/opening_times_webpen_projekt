document.addEventListener("DOMContentLoaded", () => {
  const colorInput = document.getElementById("color");
  const alphaInput = document.getElementById("alpha");
  const bgWithAlphaInput = document.getElementById("bg-with-alpha");

  function hexToRgba(hex, alpha) {
    // erwartet #RRGGBB
    const h = (hex || "").trim();
    if (!/^#[0-9a-f]{6}$/i.test(h)) return null;
    const r = parseInt(h.slice(1, 3), 16);
    const g = parseInt(h.slice(3, 5), 16);
    const b = parseInt(h.slice(5, 7), 16);
    const a = Math.max(0, Math.min(1, alpha));
    return `rgba(${r},${g},${b},${a})`;
  }

  function updateBackgroundColor() {
    const a = (parseInt(alphaInput.value, 10) || 0) / 100;
    const rgba = hexToRgba(colorInput.value, a);
    if (rgba) {
      bgWithAlphaInput.value = rgba;
      bgWithAlphaInput.style.backgroundColor = rgba;
    }
  }

  colorInput.addEventListener("input", updateBackgroundColor);
  alphaInput.addEventListener("input", updateBackgroundColor);

  // Initiale Vorschau setzen
  updateBackgroundColor();
});
