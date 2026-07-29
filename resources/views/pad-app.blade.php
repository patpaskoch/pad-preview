<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0">
<meta name="theme-color" content="#0e1013">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Pad Preview">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('img/icon-192.png') }}">
<title>Pad Preview</title>
<style>
  :root{
    --bg: #0e1013;
    --panel: #16191d;
    --panel-2: #1d2126;
    --line: #2a2f36;
    --text: #e8e6e0;
    --muted: #8b9099;
    --accent: #c98a3d;
  }
  *{box-sizing:border-box;}
  html, body{
    height:100%;
    margin:0;
    overscroll-behavior:none;
  }
  body{
    background:var(--bg);
    color:var(--text);
    font-family: 'Segoe UI', system-ui, sans-serif;
  }

  #stage{
    position:fixed;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:16px;
    padding-bottom:calc(16px + env(safe-area-inset-bottom));
  }
  #displayCanvas{
    max-width:100%;
    max-height:100%;
    display:block;
    touch-action:none;
    border-radius:10px;
    box-shadow:0 10px 40px rgba(0,0,0,.5);
  }
  .placeholder{
    color:var(--muted);
    font-size:.95rem;
    line-height:1.6;
    text-align:center;
    padding:0 30px;
    max-width:360px;
  }
  .placeholder b{ color:var(--text); }
  .placeholder .welcome-title{
    display:block;
    color:var(--text);
    font-size:1.3rem;
    font-weight:700;
    margin-bottom:8px;
  }
  .placeholder .privacy-link{
    font-size:.78rem;
    color:var(--muted);
    text-decoration:underline;
  }

  /* dock: wraps the navbar + float-controls as a single fixed-positioned,
     collapsible unit. children lay out normally (flex column) inside it,
     which keeps the "how tall is this thing" math in one place (JS reads
     dock.offsetHeight instead of guessing per-panel bottom offsets). */
  #dock{
    position:fixed;
    left:50%;
    bottom:calc(44px + env(safe-area-inset-bottom));
    transform:translateX(-50%);
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:10px;
    z-index:50;
    max-width:calc(100vw - 16px);
    width:max-content;
    transition:transform .25s ease, opacity .2s ease;
  }
  #dock.collapsed{
    transform:translateX(-50%) translateY(calc(100% + 30px));
    opacity:0;
    pointer-events:none;
  }

  .dock-toggle{
    position:fixed;
    left:50%;
    bottom:calc(8px + env(safe-area-inset-bottom));
    transform:translateX(-50%);
    z-index:60;
    width:44px;
    height:26px;
    border-radius:14px;
    background:rgba(30,34,40,.6);
    backdrop-filter: blur(14px) saturate(180%);
    -webkit-backdrop-filter: blur(14px) saturate(180%);
    border:1px solid rgba(255,255,255,.1);
    color:var(--muted);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:.85rem;
    line-height:1;
    cursor:pointer;
    box-shadow:0 6px 20px rgba(0,0,0,.4);
  }

  /* floating glass navbar */
  nav.navbar{
    display:flex;
    gap:4px;
    padding:8px;
    border-radius:22px;
    background:rgba(30,34,40,.55);
    backdrop-filter: blur(18px) saturate(180%);
    -webkit-backdrop-filter: blur(18px) saturate(180%);
    border:1px solid rgba(255,255,255,.09);
    box-shadow:0 10px 30px rgba(0,0,0,.45);
    max-width:100%;
  }
  nav.navbar button{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:3px;
    background:transparent;
    border:none;
    color:var(--muted);
    padding:9px 18px;
    border-radius:16px;
    font-size:.66rem;
    font-family:inherit;
    cursor:pointer;
    transition:background .15s, color .15s;
    flex-shrink:0;
  }
  nav.navbar button .icon{ font-size:1.25rem; line-height:1; }
  nav.navbar button.active{
    background:rgba(201,138,61,.22);
    color:var(--accent);
  }
  nav.navbar button:disabled{ opacity:.35; }

  /* floating controls above navbar, shown while editing horse or pad */
  .float-controls{
    display:none;
    align-items:center;
    gap:10px;
    padding:9px 10px 9px 16px;
    border-radius:18px;
    background:rgba(30,34,40,.55);
    backdrop-filter: blur(18px) saturate(180%);
    -webkit-backdrop-filter: blur(18px) saturate(180%);
    border:1px solid rgba(255,255,255,.09);
    box-shadow:0 10px 30px rgba(0,0,0,.45);
    font-size:.75rem;
    color:var(--muted);
    max-width:100%;
  }
  .float-controls.show{ display:flex; }
  .float-controls .fc-scroll{
    display:flex;
    align-items:center;
    gap:10px;
    overflow-x:auto;
    scrollbar-width:none;
    -webkit-overflow-scrolling:touch;
  }
  .float-controls .fc-scroll::-webkit-scrollbar{ display:none; }
  .float-controls input[type=range]{ width:90px; }
  .float-controls .opacity-group{ display:none; align-items:center; gap:8px; flex-shrink:0; }
  .float-controls .opacity-group.show{ display:flex; }
  .preset-strip{ display:none; align-items:center; gap:6px; flex-shrink:0; }
  .preset-strip.show{ display:flex; }
  .preset-strip .swatch{
    width:34px; height:34px;
    border-radius:8px;
    background-size:cover;
    background-position:center;
    border:2px solid rgba(255,255,255,.15);
    cursor:pointer;
    flex-shrink:0;
    padding:0;
  }
  .preset-strip .swatch.active{ border-color:var(--accent); }
  .float-controls button{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:2px;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.1);
    color:var(--text);
    padding:7px 12px;
    border-radius:10px;
    font-size:.62rem;
    font-family:inherit;
    cursor:pointer;
    white-space:nowrap;
    flex-shrink:0;
  }
  .float-controls button .icon{ font-size:1.05rem; line-height:1; }
  .float-controls button.done{
    background:var(--accent);
    border-color:var(--accent);
    color:#181008;
    font-weight:700;
    flex-shrink:0;
  }

  /* short / landscape viewports: tighten everything so the photo keeps most of the screen */
  @media (orientation:landscape) and (max-height:520px){
    nav.navbar button{ padding:6px 12px; font-size:.6rem; }
    nav.navbar button .icon{ font-size:1.05rem; }
    .float-controls{ padding:6px 8px 6px 12px; }
    .float-controls button{ padding:5px 9px; }
  }

  /* wider phones in portrait: let the panel stretch out so more buttons fit in one row */
  @media (min-width:480px){
    #dock{ max-width:calc(100vw - 40px); }
  }

  input[type=file]{ display:none; }

  /* crop modal */
  .modal-backdrop{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.7);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:100;
    padding:20px;
  }
  .modal{
    background:var(--panel);
    border:1px solid var(--line);
    border-radius:14px;
    max-width:560px;
    width:100%;
    max-height:calc(100vh - 40px);
    display:flex;
    flex-direction:column;
    overflow:hidden;
  }
  .modal-scroll{
    padding:18px 18px 0;
    overflow-y:auto;
  }
  .modal h3{
    margin:0 0 4px;
    font-size:1rem;
  }
  .modal p{
    margin:0 0 12px;
    color:var(--muted);
    font-size:.82rem;
  }
  .modal canvas{
    width:100%;
    height:auto;
    border-radius:8px;
    touch-action:none;
    cursor:move;
    display:block;
  }
  .modal .modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding:14px 18px;
    flex-shrink:0;
    border-top:1px solid var(--line);
  }
  .modal button{
    background:var(--panel-2);
    border:1px solid var(--line);
    color:var(--text);
    padding:9px 16px;
    border-radius:8px;
    font-size:.82rem;
    cursor:pointer;
  }
  .modal button.primary{
    background:var(--accent);
    border-color:var(--accent);
    color:#181008;
    font-weight:600;
  }

  /* before/after compare */
  .compare-backdrop{
    position:fixed;
    inset:0;
    background:#000;
    z-index:200;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:16px;
    padding:20px;
  }
  .compare-wrap{
    position:relative;
    max-width:100%;
    max-height:calc(100% - 60px);
    line-height:0;
    touch-action:none;
    cursor:ew-resize;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 10px 40px rgba(0,0,0,.5);
  }
  .compare-img{
    display:block;
    max-width:100%;
    max-height:calc(100vh - 100px);
    -webkit-user-select:none;
    user-select:none;
    pointer-events:none;
  }
  .compare-before{
    position:absolute;
    top:0; left:0;
    width:100%;
    height:100%;
    clip-path:inset(0 50% 0 0);
  }
  .compare-handle{
    position:absolute;
    top:0; bottom:0;
    left:50%;
    width:3px;
    background:var(--accent);
    transform:translateX(-50%);
    pointer-events:none;
  }
  .compare-handle::before{
    content:'';
    position:absolute;
    top:50%; left:50%;
    width:34px; height:34px;
    transform:translate(-50%,-50%);
    border-radius:50%;
    background:var(--accent);
    box-shadow:0 4px 14px rgba(0,0,0,.4);
  }
  .compare-close{
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.15);
    color:var(--text);
    padding:9px 16px;
    border-radius:10px;
    font-size:.85rem;
    cursor:pointer;
  }

  /* feedback */
  @keyframes ponyTrot{
    0%, 100% { transform: translateY(0) rotate(0deg); }
    25%      { transform: translateY(-3px) rotate(-4deg); }
    50%      { transform: translateY(0) rotate(0deg); }
    75%      { transform: translateY(-1px) rotate(3deg); }
  }
  .feedback-fab{
    position:fixed;
    right:14px;
    bottom:calc(14px + env(safe-area-inset-bottom));
    z-index:60;
    width:64px; height:64px;
    border-radius:50%;
    background:radial-gradient(circle, #1d2126, #0e1013);
    border:1px solid rgba(255,255,255,.15);
    cursor:pointer;
    box-shadow:0 6px 20px rgba(0,0,0,.5);
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    padding:0;
    animation:ponyTrot 2.6s ease-in-out infinite;
  }
  .feedback-fab:hover{ animation-duration:.7s; }
  /* mobile portrait: move it up top so it doesn't crowd the dock down below */
  @media (orientation:portrait) and (max-width:700px){
    .feedback-fab{
      top:calc(14px + env(safe-area-inset-top));
      bottom:auto;
    }
  }
  .feedback-fab img{
    width:100%;
    height:100%;
    object-fit:contain;
    transform:scale(1.6);
  }
  .feedback-modal .modal{ max-width:420px; }
  .feedback-modal textarea{
    width:100%;
    min-height:100px;
    background:#1d2126;
    border:1px solid var(--line);
    border-radius:8px;
    color:var(--text);
    padding:10px;
    font-family:inherit;
    font-size:.85rem;
    resize:vertical;
    margin-top:10px;
  }
  .feedback-modal select, .feedback-modal input[type=file], .feedback-modal input[type=email]{
    width:100%;
    background:#1d2126;
    border:1px solid var(--line);
    border-radius:8px;
    color:var(--text);
    padding:9px 10px;
    font-family:inherit;
    font-size:.85rem;
    margin-top:10px;
  }
  .feedback-modal input[type=file]{ padding:7px; }
  .feedback-status{ font-size:.8rem; color:var(--muted); margin-top:10px; min-height:1.2em; }
  .privacy-hint{ font-size:.72rem; color:var(--muted); margin:6px 0 0; }
  .privacy-hint a{ color:var(--accent); }
</style>
</head>
<body>

<div id="stage">
  <div class="placeholder" id="placeholder">
    <span class="welcome-title">🤠 Howdy, partner!</span>
    Happy you're here &mdash; let's create something stunning.<br><br>
    Tap <b>Horse</b> below to upload your horse photo, then <b>Pad</b> to add the saddle blanket.
    <br><br>
    <a href="{{ url('/privacy') }}" class="privacy-link">Privacy</a>
  </div>
  <canvas id="displayCanvas" style="display:none;"></canvas>
</div>

<div id="dock">
  <div class="float-controls" id="floatControls">
    <div class="fc-scroll">
      <button id="swapPhoto"><span class="icon">🔄</span>Change photo</button>
      <button id="cropAgain"><span class="icon">✂️</span>Crop</button>
      <button id="resetActive"><span class="icon">↺</span>Reset</button>
      <button id="flipPad" style="display:none;"><span class="icon">↔️</span>Flip</button>
      <div class="opacity-group" id="opacityGroup">
        <span>Opacity</span>
        <input type="range" id="opacitySlider" min="30" max="100" value="100">
        <span id="opacityVal">100%</span>
      </div>
      <div class="opacity-group" id="tintGroup">
        <span>Tint</span>
        <input type="range" id="tintSlider" min="-180" max="180" value="0">
      </div>
      <div class="preset-strip" id="presetStrip"></div>
    </div>
    <button class="done" id="doneEditing"><span class="icon">✓</span>Done</button>
  </div>

  <nav class="navbar">
    <button id="navHorse"><span class="icon">🐴</span>Horse</button>
    <button id="navPad"><span class="icon">🟪</span>Pad</button>
    <button id="navCompare" disabled><span class="icon">🔀</span>Compare</button>
    <button id="navFullscreen" disabled><span class="icon">⛶</span>Fullscreen</button>
    <button id="navSave" disabled><span class="icon">💾</span>Save</button>
  </nav>
</div>

<button class="dock-toggle" id="dockToggle" title="Show/hide controls">⌄</button>

<input type="file" id="horseFileInput" accept="image/*">
<input type="file" id="padFileInput" accept="image/*">

<div class="compare-backdrop" id="compareBackdrop" style="display:none;">
  <div class="compare-wrap" id="compareWrap">
    <img class="compare-img" id="compareAfterImg" alt="With pad">
    <img class="compare-img compare-before" id="compareBeforeImg" alt="Without pad">
    <div class="compare-handle" id="compareHandle"></div>
  </div>
  <button class="compare-close" id="compareClose">✕ Close</button>
</div>

<div class="modal-backdrop" id="cropModal" style="display:none;">
  <div class="modal">
    <div class="modal-scroll">
      <h3 id="cropTitle">Crop</h3>
      <p>Drag the corners to adjust, drag the middle to move &mdash; only the frame will be used.</p>
      <canvas id="cropCanvas"></canvas>
    </div>
    <div class="modal-actions">
      <button id="cropChangePhoto" style="margin-right:auto;">Change photo</button>
      <button id="cropSkip">Use whole photo</button>
      <button class="primary" id="cropApply">Apply</button>
    </div>
  </div>
</div>

<button class="feedback-fab" id="feedbackFab" title="Send feedback">
  <img src="{{ asset('img/pony-express.png') }}" alt="Send feedback">
</button>

<div class="modal-backdrop feedback-modal" id="feedbackModal" style="display:none;">
  <div class="modal">
    <div class="modal-scroll">
      <h3>🐴 Pony Express</h3>
      <p>Bug, idea, or just want to say hi? Send it our way &mdash; the pony's fast.</p>
      <form id="feedbackForm">
        <select name="category">
          <option value="bug">Bug</option>
          <option value="idea">Idea</option>
          <option value="other" selected>Other</option>
        </select>
        <textarea name="text" placeholder="What's on your mind?" required></textarea>
        <input type="email" name="email" placeholder="Email (optional, if you'd like a reply)">
        <p class="privacy-hint">Only used to reply to you &mdash; never shared. See our <a href="{{ url('/privacy') }}" target="_blank">privacy policy</a>.</p>
        <input type="file" name="screenshot" accept="image/*">
        <div class="feedback-status" id="feedbackStatus"></div>
      </form>
    </div>
    <div class="modal-actions">
      <button id="feedbackCancel">Not now</button>
      <button class="primary" id="feedbackSubmit">Send</button>
    </div>
  </div>
</div>

<script>
// ---- lightweight usage tracking (see backlog #5) ----
// fires small POSTs to /log; failures are silently ignored so tracking can
// never break the app itself.
function logEvent(type, meta){
  try{
    fetch('{{ route('log.store') }}', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ type, meta, referrer: document.referrer || null }),
      keepalive: true,
    }).catch(() => {});
  }catch(e){}
}

const sessionStart = Date.now();
logEvent('pageview');

function sendSessionEnd(){
  const payload = JSON.stringify({ type: 'session_end', meta: { seconds: Math.round((Date.now() - sessionStart)/1000) } });
  if(navigator.sendBeacon){
    navigator.sendBeacon('{{ route('log.store') }}', new Blob([payload], {type:'application/json'}));
  }
}
document.addEventListener('visibilitychange', () => { if(document.visibilityState === 'hidden') sendSessionEnd(); });

if('serviceWorker' in navigator){
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
  });
}

const placeholder = document.getElementById('placeholder');
const displayCanvas = document.getElementById('displayCanvas');
const displayCtx = displayCanvas.getContext('2d');

const navHorse = document.getElementById('navHorse');
const navPad = document.getElementById('navPad');
const navCompare = document.getElementById('navCompare');
const navSave = document.getElementById('navSave');
const navFullscreen = document.getElementById('navFullscreen');
const dock = document.getElementById('dock');
const dockToggle = document.getElementById('dockToggle');
const floatControls = document.getElementById('floatControls');
const opacityGroup = document.getElementById('opacityGroup');
const opacitySlider = document.getElementById('opacitySlider');
const opacityVal = document.getElementById('opacityVal');

// ---- collapsible dock (navbar + float-controls) ----
// while actively editing (handles + toolbar visible), the stage reserves
// room below the photo so the dock doesn't sit on top of what you're
// dragging. once you're done editing (just browsing the result), the photo
// goes full-bleed and the compact navbar floats on top of it instead.
let dockCollapsed = false;

dockToggle.addEventListener('click', () => {
  dockCollapsed = !dockCollapsed;
  dock.classList.toggle('collapsed', dockCollapsed);
  dockToggle.textContent = dockCollapsed ? '⌃' : '⌄';
  if(dockCollapsed) logEvent('fullscreen_toggled');
  syncStagePadding();
});

function syncStagePadding(){
  const stageEl = document.getElementById('stage');
  if(!editTarget || dockCollapsed){
    stageEl.style.paddingBottom = 'calc(16px + env(safe-area-inset-bottom))';
    return;
  }
  const toggleReserve = 44; // matches #dock's base "bottom" offset in CSS
  const margin = 16;
  const reserved = toggleReserve + dock.offsetHeight + margin;
  stageEl.style.paddingBottom = `calc(${reserved}px + env(safe-area-inset-bottom))`;
}

window.addEventListener('resize', syncStagePadding);
window.addEventListener('orientationchange', () => setTimeout(syncStagePadding, 50));

const horseFileInput = document.getElementById('horseFileInput');
const padFileInput = document.getElementById('padFileInput');

let horseRawImg = null;   // original uploaded horse photo (for re-crop)
let padRawImg = null;     // original uploaded pad photo (for re-crop)

// both layers share the same shape: { img, x, y, w, h, rot }
// x/y = center, w/h = drawn size, rot = radians. img is a drawable (Image or canvas).
let objects = { horse: null, pad: null };
let padOpacity = 1.0;
let padHue = 0; // -180..180, CSS hue-rotate degrees applied to the pad layer
let padHistory = []; // [{img, thumb}] recently used pad photos, newest first

let editTarget = null; // null | 'horse' | 'pad'
let dragMode = null;   // 'move' | 'resize' | 'rotate'
let dragStart = null;

const HANDLE_R = 14;
const ROT_OFFSET = 34;

function readAsImage(file, cb){
  const reader = new FileReader();
  reader.onload = ev => {
    const img = new Image();
    img.onload = () => cb(img);
    img.src = ev.target.result;
  };
  reader.readAsDataURL(file);
}

// ---- nav bar ----
navHorse.addEventListener('click', () => {
  if(!horseRawImg){ horseFileInput.click(); return; }
  editTarget = (editTarget === 'horse') ? null : 'horse';
  updateUI();
});
navPad.addEventListener('click', () => {
  if(!padRawImg){ padFileInput.click(); return; }
  editTarget = (editTarget === 'pad') ? null : 'pad';
  updateUI();
});
navSave.addEventListener('click', () => {
  if(!objects.horse) return;
  logEvent('export');
  const wasPadded = canvasOffset > 0;
  if(wasPadded){ canvasOffset = 0; displayCanvas.width = frameContentW; displayCanvas.height = frameContentH; }
  render(false);
  const link = document.createElement('a');
  link.download = 'pad-preview.png';
  link.href = displayCanvas.toDataURL('image/png');
  link.click();
  if(wasPadded) applyCanvasMode();
  render();
});

// ---- fullscreen (real Fullscreen API, standard expand icon) ----
navFullscreen.addEventListener('click', () => {
  if(navFullscreen.disabled) return;
  if(!document.fullscreenElement){
    (document.getElementById('stage').requestFullscreen?.() || Promise.resolve()).catch(() => {});
  } else {
    document.exitFullscreen?.();
  }
});
document.addEventListener('fullscreenchange', () => {
  const active = !!document.fullscreenElement;
  navFullscreen.querySelector('.icon').textContent = active ? '⤡' : '⛶';
  navFullscreen.classList.toggle('active', active);
  if(active) logEvent('fullscreen_toggled');
});

// ---- before/after compare ----
const compareBackdrop = document.getElementById('compareBackdrop');
const compareWrap = document.getElementById('compareWrap');
const compareBeforeImg = document.getElementById('compareBeforeImg');
const compareAfterImg = document.getElementById('compareAfterImg');
const compareHandle = document.getElementById('compareHandle');
let compareDragging = false;

navCompare.addEventListener('click', () => {
  if(!objects.horse || !objects.pad) return;
  compareAfterImg.src = renderLayerToDataURL(true);
  compareBeforeImg.src = renderLayerToDataURL(false);
  setCompareReveal(50);
  compareBackdrop.style.display = 'flex';
});
document.getElementById('compareClose').addEventListener('click', () => {
  compareBackdrop.style.display = 'none';
});

function setCompareReveal(pct){
  pct = Math.max(0, Math.min(100, pct));
  compareBeforeImg.style.clipPath = `inset(0 ${100 - pct}% 0 0)`;
  compareHandle.style.left = pct + '%';
}
function compareRevealFromClientX(clientX){
  const rect = compareWrap.getBoundingClientRect();
  setCompareReveal(((clientX - rect.left) / rect.width) * 100);
}
compareWrap.addEventListener('mousedown', e => { compareDragging = true; compareRevealFromClientX(e.clientX); });
window.addEventListener('mousemove', e => { if(compareDragging) compareRevealFromClientX(e.clientX); });
window.addEventListener('mouseup', () => { compareDragging = false; });
compareWrap.addEventListener('touchstart', e => { compareDragging = true; compareRevealFromClientX(e.touches[0].clientX); }, {passive:true});
compareWrap.addEventListener('touchmove', e => { if(compareDragging) compareRevealFromClientX(e.touches[0].clientX); }, {passive:true});
compareWrap.addEventListener('touchend', () => { compareDragging = false; });

horseFileInput.addEventListener('change', e => {
  if(!e.target.files[0]) return;
  logEvent('upload_horse');
  readAsImage(e.target.files[0], img => {
    horseRawImg = img;
    savedCropRect.horse = null;
    openCropModal(img, 'horse', 'Crop horse photo');
  });
  e.target.value = '';
});
padFileInput.addEventListener('change', e => {
  if(!e.target.files[0]) return;
  logEvent('upload_pad');
  readAsImage(e.target.files[0], img => {
    padRawImg = img;
    savedCropRect.pad = null;
    openCropModal(img, 'pad', 'Crop pad photo');
  });
  e.target.value = '';
});

document.getElementById('swapPhoto').addEventListener('click', () => {
  if(editTarget === 'horse') horseFileInput.click();
  else if(editTarget === 'pad') padFileInput.click();
});

document.getElementById('doneEditing').addEventListener('click', () => {
  editTarget = null;
  updateUI();
});
document.getElementById('cropAgain').addEventListener('click', () => {
  if(!editTarget) return;
  const raw = editTarget === 'horse' ? horseRawImg : padRawImg;
  const title = editTarget === 'horse' ? 'Crop horse photo' : 'Crop pad photo';
  openCropModal(raw, editTarget, title);
});
// tap once to arm, tap again within a couple seconds to actually reset —
// guards against accidental taps without an intrusive native confirm() dialog
const resetBtn = document.getElementById('resetActive');
let resetArmed = false;
let resetArmTimer = null;

function disarmReset(){
  resetArmed = false;
  resetBtn.classList.remove('done');
  resetBtn.innerHTML = '<span class="icon">↺</span>Reset';
  clearTimeout(resetArmTimer);
}

resetBtn.addEventListener('click', () => {
  if(!editTarget || !objects[editTarget]) return;

  if(!resetArmed){
    resetArmed = true;
    resetBtn.classList.add('done');
    resetBtn.innerHTML = '<span class="icon">⚠️</span>Tap again';
    resetArmTimer = setTimeout(disarmReset, 2500);
    return;
  }

  disarmReset();
  const obj = objects[editTarget];
  if(editTarget === 'horse'){
    obj.x = frameContentW/2; obj.y = frameContentH/2;
    obj.w = frameContentW; obj.h = frameContentH; obj.rot = 0;
  } else {
    const w = frameContentW * 0.4;
    const h = w * (obj.img.height / obj.img.width);
    obj.x = frameContentW/2; obj.y = frameContentH/2;
    obj.w = w; obj.h = h; obj.rot = 0; obj.flip = false;
  }
  render();
});
opacitySlider.addEventListener('input', e => {
  padOpacity = parseInt(e.target.value) / 100;
  opacityVal.textContent = e.target.value + '%';
  render();
});
document.getElementById('flipPad').addEventListener('click', () => {
  if(!objects.pad) return;
  objects.pad.flip = !objects.pad.flip;
  logEvent('transform_used', { target: 'pad', mode: 'flip' });
  render();
});
document.getElementById('tintSlider').addEventListener('input', e => {
  padHue = parseInt(e.target.value);
  render();
});

function updateUI(){
  navHorse.classList.toggle('active', editTarget === 'horse');
  navPad.classList.toggle('active', editTarget === 'pad');
  navSave.disabled = !objects.horse;
  navCompare.disabled = !(objects.horse && objects.pad);
  navFullscreen.disabled = !(objects.horse && objects.pad);
  floatControls.classList.toggle('show', !!editTarget);
  opacityGroup.classList.toggle('show', editTarget === 'pad');
  document.getElementById('tintGroup').classList.toggle('show', editTarget === 'pad');
  document.getElementById('flipPad').style.display = editTarget === 'pad' ? 'flex' : 'none';
  renderPresetStrip();
  disarmReset();
  applyCanvasMode();
  render();
  syncStagePadding();
}

// ---- horse defines the working frame size ----
// object x/y/w/h always live in "content space" (0..frameContentW/H), i.e.
// the horse photo's own pixel size with zero padding. the canvas itself
// only grows a temporary margin around that content — filled with the page
// background color, so it's invisible — while the horse layer specifically
// is being edited, so its rotate/resize handles have room to render without
// getting clipped at the edge. any other time (including the final result)
// the canvas matches the content exactly: zero padding.
const FRAME_MARGIN = 64;
let frameContentW = 0, frameContentH = 0;
let canvasOffset = 0; // current margin (0 or FRAME_MARGIN), applied when drawing/hit-testing

function applyCanvasMode(){
  if(!objects.horse) return;
  const padded = editTarget === 'horse';
  canvasOffset = padded ? FRAME_MARGIN : 0;
  displayCanvas.width = frameContentW + canvasOffset * 2;
  displayCanvas.height = frameContentH + canvasOffset * 2;
}

function setupFrame(img){
  const oldContentW = frameContentW || null;
  const oldContentH = frameContentH || null;

  // size the working resolution to the actual screen (times pixel ratio, for
  // retina sharpness) instead of a fixed cap, so the photo can fill however
  // much space is available in both landscape and portrait, on phone or
  // desktop, and never upscale past the source photo's own resolution.
  // the dock floats on top of the photo, so it doesn't need to be reserved for.
  const dpr = Math.min(window.devicePixelRatio || 1, 2);
  const availW = Math.max(320, window.innerWidth - 32) * dpr;
  const availH = Math.max(320, window.innerHeight - 32) * dpr;
  const scale = Math.min(1, availW / img.width, availH / img.height);
  frameContentW = Math.round(img.width * scale);
  frameContentH = Math.round(img.height * scale);

  objects.horse = { img, x: frameContentW/2, y: frameContentH/2, w: frameContentW, h: frameContentH, rot: 0 };

  if(objects.pad && oldContentW && oldContentH){
    const sx = frameContentW / oldContentW, sy = frameContentH / oldContentH;
    objects.pad.x *= sx; objects.pad.y *= sy;
    objects.pad.w *= sx; objects.pad.h *= sy;
  }

  placeholder.style.display = 'none';
  displayCanvas.style.display = 'block';
  editTarget = 'horse';
  updateUI();
}

function getPos(e){
  const rect = displayCanvas.getBoundingClientRect();
  const scaleX = displayCanvas.width / rect.width;
  const scaleY = displayCanvas.height / rect.height;
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  return { x: (clientX - rect.left) * scaleX - canvasOffset, y: (clientY - rect.top) * scaleY - canvasOffset };
}

// convert a world (canvas) point into an object's local, unrotated space
function toLocal(pt, obj){
  const dx = pt.x - obj.x, dy = pt.y - obj.y;
  const cos = Math.cos(-obj.rot), sin = Math.sin(-obj.rot);
  return { x: dx*cos - dy*sin, y: dx*sin + dy*cos };
}

function pointInObj(pos, obj){
  const local = toLocal(pos, obj);
  return Math.abs(local.x) < obj.w/2 && Math.abs(local.y) < obj.h/2;
}

function start(e){
  const pos = getPos(e);

  if(!editTarget){
    // tapping directly on the pad or horse opens edit mode for it
    let hit = null;
    if(objects.pad && pointInObj(pos, objects.pad)) hit = 'pad';
    else if(objects.horse && pointInObj(pos, objects.horse)) hit = 'horse';
    if(!hit) return;
    editTarget = hit;
    updateUI();
  }

  const obj = objects[editTarget];
  if(!obj) return;
  e.preventDefault();
  const local = toLocal(pos, obj);
  const rotHandle = { x: 0, y: -obj.h/2 - ROT_OFFSET };
  const resizeHandle = { x: obj.w/2, y: obj.h/2 };

  if(Math.hypot(local.x-rotHandle.x, local.y-rotHandle.y) < HANDLE_R){
    dragMode = 'rotate';
  } else if(Math.hypot(local.x-resizeHandle.x, local.y-resizeHandle.y) < HANDLE_R){
    dragMode = 'resize';
    dragStart = { w0: obj.w, h0: obj.h, r0: Math.hypot(obj.w/2, obj.h/2) };
  } else if(Math.abs(local.x) < obj.w/2 && Math.abs(local.y) < obj.h/2){
    dragMode = 'move';
    dragStart = { offX: pos.x - obj.x, offY: pos.y - obj.y };
  } else {
    dragMode = null;
  }
}
function move(e){
  if(!dragMode || !editTarget) return;
  const obj = objects[editTarget];
  if(!obj) return;
  e.preventDefault();
  const pos = getPos(e);

  if(dragMode === 'move'){
    obj.x = pos.x - dragStart.offX;
    obj.y = pos.y - dragStart.offY;
  } else if(dragMode === 'resize'){
    const local = toLocal(pos, obj);
    const r = Math.hypot(local.x, local.y);
    const scale = Math.max(0.15, r / dragStart.r0);
    obj.w = dragStart.w0 * scale;
    obj.h = dragStart.h0 * scale;
  } else if(dragMode === 'rotate'){
    obj.rot = Math.atan2(pos.y - obj.y, pos.x - obj.x) + Math.PI/2;
  }
  render();
}
function end(){
  if(dragMode) logEvent('transform_used', { target: editTarget, mode: dragMode });
  dragMode = null; dragStart = null;
}

// two-finger pinch: scale + rotate the active object together, like a
// standard photo editor gesture. takes over from single-finger dragging
// the moment a second finger touches down.
function touchDist(t1, t2){ return Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY); }
function touchAngle(t1, t2){ return Math.atan2(t2.clientY - t1.clientY, t2.clientX - t1.clientX); }

function touchStart(e){
  if(e.touches.length >= 2 && editTarget && objects[editTarget]){
    e.preventDefault();
    const obj = objects[editTarget];
    const [t1, t2] = e.touches;
    dragMode = 'pinch';
    dragStart = { d0: touchDist(t1, t2), a0: touchAngle(t1, t2), w0: obj.w, h0: obj.h, rot0: obj.rot };
    return;
  }
  start(e);
}
function touchMove(e){
  if(dragMode === 'pinch' && e.touches.length >= 2 && editTarget){
    e.preventDefault();
    const obj = objects[editTarget];
    if(!obj) return;
    const [t1, t2] = e.touches;
    const scale = Math.max(0.15, touchDist(t1, t2) / dragStart.d0);
    obj.w = dragStart.w0 * scale;
    obj.h = dragStart.h0 * scale;
    obj.rot = dragStart.rot0 + (touchAngle(t1, t2) - dragStart.a0);
    render();
    return;
  }
  move(e);
}
function touchEnd(e){
  if(e.touches.length < 2){ dragMode = null; dragStart = null; }
  end(e);
}

displayCanvas.addEventListener('mousedown', start);
displayCanvas.addEventListener('mousemove', move);
window.addEventListener('mouseup', end);
displayCanvas.addEventListener('touchstart', touchStart, {passive:false});
displayCanvas.addEventListener('touchmove', touchMove, {passive:false});
displayCanvas.addEventListener('touchend', touchEnd);

function drawObj(ctx, obj, alpha, filter){
  ctx.save();
  ctx.translate(obj.x, obj.y);
  ctx.rotate(obj.rot);
  if(obj.flip) ctx.scale(-1, 1);
  ctx.globalAlpha = alpha;
  if(filter) ctx.filter = filter;
  ctx.drawImage(obj.img, -obj.w/2, -obj.h/2, obj.w, obj.h);
  ctx.restore();
}

// soft shadow under the pad so it reads as sitting on the horse rather than
// pasted flat on top, plus whatever tint is currently dialed in
function padFilter(){
  const parts = [];
  if(padHue) parts.push(`hue-rotate(${padHue}deg)`);
  parts.push('drop-shadow(0 5px 7px rgba(0,0,0,.45))');
  return parts.join(' ');
}

function render(showHandles = true){
  if(!objects.horse) return;
  displayCtx.clearRect(0, 0, displayCanvas.width, displayCanvas.height);
  displayCtx.fillStyle = '#0e1013';
  displayCtx.fillRect(0, 0, displayCanvas.width, displayCanvas.height);

  displayCtx.save();
  displayCtx.translate(canvasOffset, canvasOffset);

  drawObj(displayCtx, objects.horse, 1);
  if(objects.pad) drawObj(displayCtx, objects.pad, padOpacity, padFilter());

  if(showHandles && editTarget && objects[editTarget]) drawHandles(objects[editTarget]);
  displayCtx.restore();
}

// renders horse (+ optionally pad) at full content resolution, no margin/handles —
// used for the before/after compare view
function renderLayerToDataURL(includePad){
  const c = document.createElement('canvas');
  c.width = frameContentW; c.height = frameContentH;
  const ctx = c.getContext('2d');
  ctx.fillStyle = '#0e1013';
  ctx.fillRect(0, 0, c.width, c.height);
  drawObj(ctx, objects.horse, 1);
  if(includePad && objects.pad){
    drawObj(ctx, objects.pad, padOpacity, padFilter());
  }
  return c.toDataURL('image/png');
}

function drawHandles(obj){
  displayCtx.save();
  displayCtx.translate(obj.x, obj.y);
  displayCtx.rotate(obj.rot);
  displayCtx.globalAlpha = 1;

  displayCtx.strokeStyle = 'rgba(201,138,61,0.9)';
  displayCtx.lineWidth = 2;
  displayCtx.setLineDash([6,4]);
  displayCtx.strokeRect(-obj.w/2, -obj.h/2, obj.w, obj.h);
  displayCtx.setLineDash([]);

  const rx = 0, ry = -obj.h/2 - ROT_OFFSET;
  displayCtx.beginPath();
  displayCtx.moveTo(0, -obj.h/2);
  displayCtx.lineTo(rx, ry);
  displayCtx.stroke();
  drawHandleDot(rx, ry);

  drawHandleDot(obj.w/2, obj.h/2);

  displayCtx.restore();
}

function drawHandleDot(x, y){
  displayCtx.beginPath();
  displayCtx.arc(x, y, 8, 0, Math.PI*2);
  displayCtx.fillStyle = '#c98a3d';
  displayCtx.strokeStyle = '#181008';
  displayCtx.lineWidth = 1.5;
  displayCtx.fill();
  displayCtx.stroke();
}

// ---- crop modal (shared by horse and pad) ----
const cropModal = document.getElementById('cropModal');
const cropTitleEl = document.getElementById('cropTitle');
const cropCanvas = document.getElementById('cropCanvas');
const cropCtx = cropCanvas.getContext('2d');
let cropImg = null;
let cropTarget = null; // 'horse' | 'pad'
let cropRect = null;   // {x1,y1,x2,y2} in cropCanvas pixel space
let savedCropRect = { horse: null, pad: null };
let cropDragMode = null;
let cropDragStart = null;
const CROP_HANDLE_R = 16;
const CROP_MIN = 24;

function openCropModal(img, target, title){
  cropImg = img;
  cropTarget = target;
  cropTitleEl.textContent = title;

  const maxW = 520;
  // also cap by available viewport height, so the modal (title + text + canvas
  // + buttons) never grows taller than the screen on short/landscape phones
  const maxH = Math.max(200, window.innerHeight - 220);
  const scale = Math.min(1, maxW / img.width, maxH / img.height);
  cropCanvas.width = Math.round(img.width * scale);
  cropCanvas.height = Math.round(img.height * scale);

  if(savedCropRect[target]){
    cropRect = { ...savedCropRect[target] };
  } else {
    const insetX = cropCanvas.width * 0.08;
    const insetY = cropCanvas.height * 0.08;
    cropRect = { x1: insetX, y1: insetY, x2: cropCanvas.width - insetX, y2: cropCanvas.height - insetY };
  }

  cropModal.style.display = 'flex';
  renderCrop();
}

function renderCrop(){
  const cw = cropCanvas.width, ch = cropCanvas.height;
  cropCtx.clearRect(0,0,cw,ch);
  cropCtx.drawImage(cropImg, 0, 0, cw, ch);

  cropCtx.save();
  cropCtx.fillStyle = 'rgba(0,0,0,0.6)';
  cropCtx.fillRect(0, 0, cw, cropRect.y1);
  cropCtx.fillRect(0, cropRect.y2, cw, ch - cropRect.y2);
  cropCtx.fillRect(0, cropRect.y1, cropRect.x1, cropRect.y2 - cropRect.y1);
  cropCtx.fillRect(cropRect.x2, cropRect.y1, cw - cropRect.x2, cropRect.y2 - cropRect.y1);
  cropCtx.restore();

  cropCtx.strokeStyle = '#c98a3d';
  cropCtx.lineWidth = 2;
  cropCtx.strokeRect(cropRect.x1, cropRect.y1, cropRect.x2 - cropRect.x1, cropRect.y2 - cropRect.y1);

  [[cropRect.x1,cropRect.y1],[cropRect.x2,cropRect.y1],[cropRect.x1,cropRect.y2],[cropRect.x2,cropRect.y2]]
    .forEach(([x,y]) => {
      cropCtx.beginPath();
      cropCtx.arc(x, y, 7, 0, Math.PI*2);
      cropCtx.fillStyle = '#c98a3d';
      cropCtx.strokeStyle = '#181008';
      cropCtx.lineWidth = 1.5;
      cropCtx.fill();
      cropCtx.stroke();
    });
}

function getCropPos(e){
  const rect = cropCanvas.getBoundingClientRect();
  const scaleX = cropCanvas.width / rect.width;
  const scaleY = cropCanvas.height / rect.height;
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  return { x: (clientX - rect.left) * scaleX, y: (clientY - rect.top) * scaleY };
}

cropCanvas.addEventListener('mousedown', cropStart);
cropCanvas.addEventListener('mousemove', cropMove);
window.addEventListener('mouseup', cropEnd);
cropCanvas.addEventListener('touchstart', cropStart, {passive:false});
cropCanvas.addEventListener('touchmove', cropMove, {passive:false});
cropCanvas.addEventListener('touchend', cropEnd);

function cropStart(e){
  if(!cropRect) return;
  e.preventDefault();
  const pos = getCropPos(e);
  const corners = {
    tl: {x: cropRect.x1, y: cropRect.y1},
    tr: {x: cropRect.x2, y: cropRect.y1},
    bl: {x: cropRect.x1, y: cropRect.y2},
    br: {x: cropRect.x2, y: cropRect.y2},
  };
  for(const key in corners){
    if(Math.hypot(pos.x - corners[key].x, pos.y - corners[key].y) < CROP_HANDLE_R){
      cropDragMode = key;
      return;
    }
  }
  if(pos.x > cropRect.x1 && pos.x < cropRect.x2 && pos.y > cropRect.y1 && pos.y < cropRect.y2){
    cropDragMode = 'move';
    cropDragStart = { offX: pos.x - cropRect.x1, offY: pos.y - cropRect.y1, w: cropRect.x2 - cropRect.x1, h: cropRect.y2 - cropRect.y1 };
  } else {
    cropDragMode = null;
  }
}

function cropMove(e){
  if(!cropDragMode) return;
  e.preventDefault();
  const pos = getCropPos(e);
  const cw = cropCanvas.width, ch = cropCanvas.height;
  const clampX = v => Math.max(0, Math.min(cw, v));
  const clampY = v => Math.max(0, Math.min(ch, v));

  if(cropDragMode === 'tl'){
    cropRect.x1 = Math.min(clampX(pos.x), cropRect.x2 - CROP_MIN);
    cropRect.y1 = Math.min(clampY(pos.y), cropRect.y2 - CROP_MIN);
  } else if(cropDragMode === 'tr'){
    cropRect.x2 = Math.max(clampX(pos.x), cropRect.x1 + CROP_MIN);
    cropRect.y1 = Math.min(clampY(pos.y), cropRect.y2 - CROP_MIN);
  } else if(cropDragMode === 'bl'){
    cropRect.x1 = Math.min(clampX(pos.x), cropRect.x2 - CROP_MIN);
    cropRect.y2 = Math.max(clampY(pos.y), cropRect.y1 + CROP_MIN);
  } else if(cropDragMode === 'br'){
    cropRect.x2 = Math.max(clampX(pos.x), cropRect.x1 + CROP_MIN);
    cropRect.y2 = Math.max(clampY(pos.y), cropRect.y1 + CROP_MIN);
  } else if(cropDragMode === 'move'){
    let x1 = pos.x - cropDragStart.offX;
    let y1 = pos.y - cropDragStart.offY;
    x1 = Math.max(0, Math.min(cw - cropDragStart.w, x1));
    y1 = Math.max(0, Math.min(ch - cropDragStart.h, y1));
    cropRect.x1 = x1; cropRect.y1 = y1;
    cropRect.x2 = x1 + cropDragStart.w; cropRect.y2 = y1 + cropDragStart.h;
  }
  renderCrop();
}

function cropEnd(){ cropDragMode = null; cropDragStart = null; }

function closeCropModal(){
  cropModal.style.display = 'none';
  cropImg = null;
  cropRect = null;
  cropTarget = null;
}

function applyCropResult(out){
  if(cropTarget === 'horse'){
    setupFrame(out);
  } else if(cropTarget === 'pad'){
    if(objects.pad){
      objects.pad.img = out;
      objects.pad.h = objects.pad.w * (out.height / out.width);
    } else if(objects.horse){
      const w = frameContentW * 0.4;
      const h = w * (out.height / out.width);
      objects.pad = { img: out, x: frameContentW/2, y: frameContentH/2, w, h, rot: 0 };
    }
    addToPadHistory(out);
    editTarget = 'pad';
    updateUI();
  }
  closeCropModal();
}

// ---- pad presets: remember recently used pad photos so they can be
// re-applied instantly (e.g. to compare colorways) without re-uploading ----
const PAD_HISTORY_MAX = 8;

function addToPadHistory(img){
  padHistory = padHistory.filter(entry => entry.img !== img);
  padHistory.unshift({ img, thumb: img.toDataURL ? img.toDataURL('image/png') : img.src });
  if(padHistory.length > PAD_HISTORY_MAX) padHistory.length = PAD_HISTORY_MAX;
}

function renderPresetStrip(){
  const strip = document.getElementById('presetStrip');
  strip.classList.toggle('show', editTarget === 'pad' && padHistory.length > 1);
  strip.innerHTML = '';
  if(editTarget !== 'pad') return;

  padHistory.forEach(entry => {
    const btn = document.createElement('button');
    btn.className = 'swatch';
    btn.style.backgroundImage = `url(${entry.thumb})`;
    if(objects.pad && objects.pad.img === entry.img) btn.classList.add('active');
    btn.addEventListener('click', () => {
      if(!objects.pad) return;
      objects.pad.img = entry.img;
      objects.pad.h = objects.pad.w * (entry.img.height / entry.img.width);
      renderPresetStrip();
      render();
    });
    strip.appendChild(btn);
  });
}

document.getElementById('cropApply').addEventListener('click', () => {
  logEvent('crop_used', { target: cropTarget });
  savedCropRect[cropTarget] = { ...cropRect };

  const scaleX = cropImg.width / cropCanvas.width;
  const scaleY = cropImg.height / cropCanvas.height;
  const sx = cropRect.x1 * scaleX, sy = cropRect.y1 * scaleY;
  const sw = (cropRect.x2 - cropRect.x1) * scaleX, sh = (cropRect.y2 - cropRect.y1) * scaleY;

  const out = document.createElement('canvas');
  out.width = Math.round(sw); out.height = Math.round(sh);
  out.getContext('2d').drawImage(cropImg, sx, sy, sw, sh, 0, 0, out.width, out.height);

  applyCropResult(out);
});

document.getElementById('cropSkip').addEventListener('click', () => {
  applyCropResult(cropImg);
});

document.getElementById('cropChangePhoto').addEventListener('click', () => {
  if(cropTarget === 'horse') horseFileInput.click();
  else if(cropTarget === 'pad') padFileInput.click();
});

// ---- feedback ----
const feedbackModal = document.getElementById('feedbackModal');
const feedbackForm = document.getElementById('feedbackForm');
const feedbackStatus = document.getElementById('feedbackStatus');
const feedbackSubmit = document.getElementById('feedbackSubmit');

document.getElementById('feedbackFab').addEventListener('click', () => {
  feedbackStatus.textContent = '';
  feedbackForm.reset();
  feedbackModal.style.display = 'flex';
});
document.getElementById('feedbackCancel').addEventListener('click', () => {
  feedbackModal.style.display = 'none';
});

feedbackSubmit.addEventListener('click', async () => {
  const text = feedbackForm.text.value.trim();
  if(!text){ feedbackStatus.textContent = 'Please write something first.'; return; }

  feedbackSubmit.disabled = true;
  feedbackStatus.textContent = 'Sending…';

  const formData = new FormData(feedbackForm);
  const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

  try{
    const res = await fetch('{{ route('feedback.store') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: formData,
    });
    if(!res.ok) throw new Error('request failed');
    feedbackStatus.textContent = 'Thanks — sent!';
    setTimeout(() => { feedbackModal.style.display = 'none'; }, 900);
  }catch(e){
    feedbackStatus.textContent = 'Could not send, please try again.';
  }finally{
    feedbackSubmit.disabled = false;
  }
});

syncStagePadding();
</script>

</body>
</html>
