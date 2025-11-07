<?php 
require_once 'config.php';

// Récupérer les paramètres d'apparence
$appearance = getAppearanceSettings($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Jeu Char Arcade - Hangar et Crédits</title>
  <style>
body { margin:0; background:#0a0a0a;font-family:'Segoe UI', Arial, sans-serif; min-height:100vh; }
#wrapper-arcade { /* Pour l'intégration avec le layout du site, ici le wrapper laisse place au header et centre le contenu.*/
  margin:auto; max-width:1100px; width:100%; padding-top:40px; display:flex; flex-direction:column; align-items:center; box-sizing:border-box;
}
#menu {
  width:100%; min-height:630px;
  display:flex; flex-direction:column; justify-content:center; align-items:center;
  color:#fff; background:linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
  animation: menuGlow 3s ease-in-out infinite alternate;
  border-radius:40px; box-shadow:0 10px 40px #16213e44;
  margin-bottom:35px;
  margin-top:35px;
  overflow-x:auto;
}
@keyframes menuGlow {
  from { background-position: 0% 50%; }
  to { background-position: 100% 50%; }
}
h1 {
  font-size:48px;
  background:linear-gradient(90deg, #00ff88, #00ccff, #ff00ff);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
  text-shadow:0 0 30px rgba(0,255,136,0.5);
  margin-bottom:30px;
  margin-top:20px;
  animation:titleGlow 2s ease-in-out infinite alternate;
}
@keyframes titleGlow {
  from { filter:drop-shadow(0 0 10px rgba(0,255,136,0.5)); }
  to { filter:drop-shadow(0 0 30px rgba(0,255,136,0.9)); }
}
.btn { 
  background:linear-gradient(135deg, #e94560 0%, #c72940 100%); 
  color:#fff; padding:15px 40px; margin:10px; cursor:pointer; font-size:22px;
  border:none; border-radius:10px; font-weight:bold; text-transform:uppercase;
  box-shadow:0 4px 15px rgba(233, 69, 96, 0.4);
  transition:all 0.3s ease;
  letter-spacing:1px;
  outline:none;
}
.btn:focus { outline:2px solid #e94560; }
.btn:hover { 
  background:linear-gradient(135deg, #ff5577 0%, #e94560 100%); 
  transform:translateY(-2px) scale(1.05);
  box-shadow:0 6px 25px rgba(233, 69, 96, 0.6);
}
.char-select {
  display:flex; justify-content:center; margin:20px; flex-wrap:wrap; max-width:900px;
}
.char-option {
  width:140px; height:160px; border:3px solid #3a506b; margin:10px; cursor:pointer; border-radius:15px;
  display:flex; justify-content:center; align-items:center; flex-direction:column; font-weight:bold; font-size:12px; text-align:center;
  opacity:1; background:linear-gradient(135deg, #1c2841 0%, #0f1419 100%);
  transition:all 0.3s ease; box-shadow:0 4px 15px rgba(0,0,0,0.5);
  position:relative; overflow:hidden; padding:10px;
}
.char-option::before {
  content:''; position:absolute; top:0; left:-100%; width:100%; height:100%;
  background:linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  transition:left 0.5s;
}
.char-option:hover::before { left:100%; }
.char-option:hover {
  transform:translateY(-5px) scale(1.05);
  border-color:#5cb3ff;
  box-shadow:0 8px 25px rgba(92, 179, 255, 0.4);
}
.char-option.locked { 
  opacity:0.4; 
  filter:grayscale(1);
  cursor:not-allowed;
}
.char-option.selected { 
  border-color:#00ff88; 
  background:linear-gradient(135deg, #1c4128 0%, #0a1f12 100%);
  box-shadow:0 0 30px rgba(0, 255, 136, 0.6);
  animation:pulse 1.5s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { box-shadow:0 0 30px rgba(0, 255, 136, 0.6); }
  50% { box-shadow:0 0 50px rgba(0, 255, 136, 0.9); }
}
.char-option .tank-preview { 
  width:70px; height:50px; margin-bottom:8px; border-radius:5px;
  box-shadow:0 4px 10px rgba(0,0,0,0.6);
}
.char-stats {
  font-size:11px;
  line-height:1.4;
  color:#aaa;
}
.char-stats strong {
  color:#00ff88;
}
#money { 
  font-size:28px; 
  margin-top:20px; 
  background:linear-gradient(90deg, #ffd700, #ffed4e, #ffd700);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
  font-weight:bold;
  text-shadow:0 0 20px rgba(255, 215, 0, 0.5);
  letter-spacing:2px;
  animation:moneyShine 2s linear infinite;
  background-size:200% 100%;
}
@keyframes moneyShine {
  0% { background-position: 0% 50%; }
  100% { background-position: 200% 50%; }
}
@media screen and (max-width:900px){
  #wrapper-arcade { max-width:100%; }
  #menu { max-width:99vw; padding:10px; }
  .char-select { flex-wrap:wrap; max-width:99vw;}
  .char-option { width:120px; height:140px; margin:6px; }
}
  </style>
</head>
<body>
<div id="wrapper-arcade">
  <div id="menu">
    <h1>🎮 HANGAR DE COMBAT 🎮</h1>
    <div class="char-select">
      <div class="char-option selected" data-char="0">
        <div class="tank-preview" style="background:linear-gradient(135deg, #999, #666);"></div>
        <strong>Recon Tank</strong>
        <div class="char-stats">Vitesse: 4<br>Vies: 3<br>Tir: Simple<br>Rechargement: 300ms<br><strong>GRATUIT</strong></div>
      </div>
      <div class="char-option locked" data-char="1" data-price="50">
        <div class="tank-preview" style="background:linear-gradient(135deg, #2E8B57, #1a5c3a);"></div>
        <strong>M1 Abrams</strong>
        <div class="char-stats">Vitesse: 5<br>Vies: 4<br>Tir: Simple<br>Rechargement: 250ms<br><strong>Prix: 50</strong></div>
      </div>
      <div class="char-option locked" data-char="2" data-price="70">
        <div class="tank-preview" style="background:linear-gradient(135deg, #4682B4, #2d5a80);"></div>
        <strong>T-90</strong>
        <div class="char-stats">Vitesse: 4<br>Vies: 5<br>Tir: Simple<br>Rechargement: 250ms<br><strong>Prix: 70</strong></div>
      </div>
      <div class="char-option locked" data-char="3" data-price="60">
        <div class="tank-preview" style="background:linear-gradient(135deg, #B22222, #8b0000);"></div>
        <strong>Leopard 2</strong>
        <div class="char-stats">Vitesse: 6<br>Vies: 3<br>Tir: Simple<br>Rechargement: 200ms<br><strong>Prix: 60</strong></div>
      </div>
      <div class="char-option locked" data-char="4" data-price="80">
        <div class="tank-preview" style="background:linear-gradient(135deg, #DAA520, #b8860b);"></div>
        <strong>Challenger 2</strong>
        <div class="char-stats">Vitesse: 4<br>Vies: 6<br>Tir: Double<br>Rechargement: 350ms<br><strong>Prix: 80</strong></div>
      </div>
      <div class="char-option locked" data-char="5" data-price="90">
        <div class="tank-preview" style="background:linear-gradient(135deg, #800080, #4b0082);"></div>
        <strong>K2 Black Panther</strong>
        <div class="char-stats">Vitesse: 5<br>Vies: 5<br>Tir: Double<br>Rechargement: 300ms<br><strong>Prix: 90</strong></div>
      </div>
      <div class="char-option locked" data-char="6" data-price="150">
        <div class="tank-preview" style="background:linear-gradient(135deg, #FF4500, #cc3700);"></div>
        <strong>Type 99</strong>
        <div class="char-stats">Vitesse: 6<br>Vies: 8<br>Tir: Triple<br>Rechargement: 250ms<br><strong>Prix: 150</strong></div>
      </div>
    </div>
    <p id="money">💰 Crédits: 0</p>
    <button class="btn" id="playBtn">⚔️ LANCER LA BATAILLE ⚔️</button>
  </div>
  <!-- Pour garantir que le canvas est toujours visible dans la page, il est intégré dans le wrapper -->
  <canvas id="gameCanvas"></canvas>
</div>
<body>
<!-- HANGAR -->
<div id="menu">
  <h1>🎮 HANGAR DE COMBAT 🎮</h1>
  <div class="char-select">
    <div class="char-option selected" data-char="0">
      <div class="tank-preview" style="background:linear-gradient(135deg, #999, #666);"></div>
      <strong>Recon Tank</strong>
      <div class="char-stats">Vitesse: 4<br>Vies: 3<br>Tir: Simple<br>Rechargement: 300ms<br><strong>GRATUIT</strong></div>
    </div>
    <div class="char-option locked" data-char="1" data-price="50">
      <div class="tank-preview" style="background:linear-gradient(135deg, #2E8B57, #1a5c3a);"></div>
      <strong>M1 Abrams</strong>
      <div class="char-stats">Vitesse: 5<br>Vies: 4<br>Tir: Simple<br>Rechargement: 250ms<br><strong>Prix: 50</strong></div>
    </div>
    <div class="char-option locked" data-char="2" data-price="70">
      <div class="tank-preview" style="background:linear-gradient(135deg, #4682B4, #2d5a80);"></div>
      <strong>T-90</strong>
      <div class="char-stats">Vitesse: 4<br>Vies: 5<br>Tir: Simple<br>Rechargement: 250ms<br><strong>Prix: 70</strong></div>
    </div>
    <div class="char-option locked" data-char="3" data-price="60">
      <div class="tank-preview" style="background:linear-gradient(135deg, #B22222, #8b0000);"></div>
      <strong>Leopard 2</strong>
      <div class="char-stats">Vitesse: 6<br>Vies: 3<br>Tir: Simple<br>Rechargement: 200ms<br><strong>Prix: 60</strong></div>
    </div>
    <div class="char-option locked" data-char="4" data-price="80">
      <div class="tank-preview" style="background:linear-gradient(135deg, #DAA520, #b8860b);"></div>
      <strong>Challenger 2</strong>
      <div class="char-stats">Vitesse: 4<br>Vies: 6<br>Tir: Double<br>Rechargement: 350ms<br><strong>Prix: 80</strong></div>
    </div>
    <div class="char-option locked" data-char="5" data-price="90">
      <div class="tank-preview" style="background:linear-gradient(135deg, #800080, #4b0082);"></div>
      <strong>K2 Black Panther</strong>
      <div class="char-stats">Vitesse: 5<br>Vies: 5<br>Tir: Double<br>Rechargement: 300ms<br><strong>Prix: 90</strong></div>
    </div>
    <div class="char-option locked" data-char="6" data-price="150">
      <div class="tank-preview" style="background:linear-gradient(135deg, #FF4500, #cc3700);"></div>
      <strong>Type 99</strong>
      <div class="char-stats">Vitesse: 6<br>Vies: 8<br>Tir: Triple<br>Rechargement: 250ms<br><strong>Prix: 150</strong></div>
    </div>
  </div>
  <p id="money">💰 Crédits: 0</p>
  <button class="btn" id="playBtn">⚔️ LANCER LA BATAILLE ⚔️</button>
</div>

<canvas id="gameCanvas"></canvas>

<script>
// INITIALISATION
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
canvas.width = 800;
canvas.height = 600;

let selectedChar = 0;
let gameStarted = false;
let money = 0;
let frameCount = 0;
let stars = [];
for(let i=0; i<100; i++){
  stars.push({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    size: Math.random() * 2,
    speed: Math.random() * 0.5 + 0.2
  });
}

const charOptions = document.querySelectorAll('.char-option');
charOptions.forEach(opt=>{
  const price = parseInt(opt.dataset.price||0);
  if(price===0) opt.classList.remove('locked');
  opt.addEventListener('click', ()=>{
    const price = parseInt(opt.dataset.price||0);
    const isLocked = opt.classList.contains('locked');
    if(!isLocked || price === 0){
      charOptions.forEach(o=>o.classList.remove('selected'));
      opt.classList.add('selected');
      selectedChar = parseInt(opt.dataset.char);
    } else if(price <= money){
      money -= price;
      opt.classList.remove('locked');
      opt.dataset.price = 0;
      charOptions.forEach(o=>o.classList.remove('selected'));
      opt.classList.add('selected');
      selectedChar = parseInt(opt.dataset.char);
      document.getElementById('money').textContent = "💰 Crédits: "+money;
    } else {
      alert("⚠️ Vous n'avez pas assez de crédits !");
    }
  });
});

document.getElementById('playBtn').addEventListener('click', ()=>{
  document.getElementById('menu').style.display='none';
  gameStarted = true;
  initGame();
  loop();
});

const tankModels = [
  {name:"Recon Tank", color:'#CCCCCC', speed:4, lives:3, shotType:"single", reloadTime:300},
  {name:"M1 Abrams", color:'#2E8B57', speed:5, lives:4, shotType:"single", reloadTime:250, price:50},
  {name:"T-90", color:'#4682B4', speed:4, lives:5, shotType:"single", reloadTime:250, price:70},
  {name:"Leopard 2", color:'#B22222', speed:6, lives:3, shotType:"single", reloadTime:200, price:60},
  {name:"Challenger 2", color:'#DAA520', speed:4, lives:6, shotType:"double", reloadTime:350, price:80},
  {name:"K2 Black Panther", color:'#800080', speed:5, lives:5, shotType:"double", reloadTime:300, price:90},
  {name:"Type 99", color:'#FF4500', speed:6, lives:8, shotType:"triple", reloadTime:250, price:150}
];

let tank = {x:400, y:500, width:40, height:50, angle:0, speed:5, lives:3, lastShot:0, canShoot:true};
let keys = {};
let bullets = [];
let enemies = [];
let level = 1;
let particles = [];
let explosions = [];
let enemySpawnTimer = 0;
let score = 0;

function initGame(){
  const model = tankModels[selectedChar];
  tank = {
    x:400, y:500, width:40, height:50, angle:0, speed:model.speed, lives:model.lives, lastShot:0, canShoot:true
  };
  bullets = [];
  enemies = [];
  particles = [];
  explosions = [];
  frameCount = 0;
  enemySpawnTimer = 0;
  spawnInitialEnemies();
}
document.addEventListener('keydown', e=>{
  keys[e.key]=true;
  if(e.key===' ' && gameStarted && tank.canShoot){
    e.preventDefault();
    shootBullet();
  }
});
document.addEventListener('keyup', e=>keys[e.key]=false);

function shootBullet(){
  const model = tankModels[selectedChar];
  const now = Date.now();
  if(now - tank.lastShot >= model.reloadTime){
    tank.lastShot = now;
    tank.canShoot = false;
    if(model.shotType==="single"){
      bullets.push({x:tank.x, y:tank.y-30, dx:0, dy:-10, life:200, fromEnemy:false});
      createMuzzleFlash(tank.x, tank.y-30);
    } 
    else if(model.shotType==="double"){
      bullets.push({x:tank.x-12, y:tank.y-30, dx:-1, dy:-10, life:200, fromEnemy:false});
      bullets.push({x:tank.x+12, y:tank.y-30, dx:1, dy:-10, life:200, fromEnemy:false});
      createMuzzleFlash(tank.x-12, tank.y-30);
      createMuzzleFlash(tank.x+12, tank.y-30);
    } 
    else if(model.shotType==="triple"){
      bullets.push({x:tank.x, y:tank.y-30, dx:0, dy:-10, life:200, fromEnemy:false});
      bullets.push({x:tank.x-15, y:tank.y-30, dx:-1.5, dy:-10, life:200, fromEnemy:false});
      bullets.push({x:tank.x+15, y:tank.y-30, dx:1.5, dy:-10, life:200, fromEnemy:false});
      createMuzzleFlash(tank.x, tank.y-30);
      createMuzzleFlash(tank.x-15, tank.y-30);
      createMuzzleFlash(tank.x+15, tank.y-30);
    }
    setTimeout(()=>{ tank.canShoot = true; }, model.reloadTime);
  }
}

function createMuzzleFlash(x, y){
  for(let i=0; i<12; i++){
    particles.push({
      x, y,
      vx: (Math.random()-0.5)*4,
      vy: (Math.random()-0.5)*4 - 3,
      life: 20,
      maxLife: 20,
      size: Math.random()*4 + 2,
      color: `rgba(255,${200+Math.random()*55},${Math.random()*100},`
    });
  }
}
function createExplosion(x, y, size){
  explosions.push({x, y, size: 0, maxSize: size, life: 40});
  for(let i=0; i<30; i++){
    particles.push({
      x, y,
      vx: (Math.random()-0.5)*8,
      vy: (Math.random()-0.5)*8,
      life: 40 + Math.random()*30,
      maxLife: 60,
      size: Math.random()*5 + 2,
      color: `rgba(${200+Math.random()*55},${Math.random()*150},0,`
    });
  }
}
function moveTank(){
  if(keys['ArrowLeft'] && tank.x>20) tank.x -= tank.speed;
  if(keys['ArrowRight'] && tank.x<canvas.width-20) tank.x += tank.speed;
  if(keys['ArrowUp'] && tank.y>20) tank.y -= tank.speed;
  if(keys['ArrowDown'] && tank.y<canvas.height-25) tank.y += tank.speed;
}

function spawnEnemy(){
  const types = ['normal', 'normal', 'normal', 'fast', 'tank'];
  const type = types[Math.floor(Math.random() * types.length)];
  if(type === 'fast'){
    enemies.push({
      x:Math.random()*(canvas.width-35), y:-50, width:35, height:35, speed:3.5, hp:1, type:"fast", lastShot:0, points:8
    });
  } else if(type === 'tank'){
    enemies.push({
      x:Math.random()*(canvas.width-50), y:-60, width:50, height:50, speed:1.5, hp:3, type:"tank", lastShot:0, points:15
    });
  } else {
    enemies.push({
      x:Math.random()*(canvas.width-40), y:-50, width:40, height:40, speed:2.5, hp:1, type:"normal", lastShot:0, points:5
    });
  }
}
function spawnMiniBoss(){
  enemies.push({
    x:Math.random()*(canvas.width-100), y:-100, width:100, height:80, speed:1.8, hp:30, type:"miniBoss", lastShot:0, points:50
  });
}
function spawnBoss(){
  enemies.push({
    x:canvas.width/2-150, y:-150, width:300, height:120, speed:1, hp:150, type:"boss", lastShot:0, points:100
  });
}
function spawnInitialEnemies(){
  for(let i=0;i<5;i++) spawnEnemy();
}
function shadeColor(color, percent) {
  const num = parseInt(color.replace("#",""), 16);
  const amt = Math.round(2.55 * percent);
  const R = Math.min(255, Math.max(0, (num >> 16) + amt));
  const G = Math.min(255, Math.max(0, (num >> 8 & 0x00FF) + amt));
  const B = Math.min(255, Math.max(0, (num & 0x0000FF) + amt));
  return "#" + (0x1000000 + R*0x10000 + G*0x100 + B).toString(16).slice(1);
}

function drawTank(){
  ctx.save();
  ctx.translate(tank.x, tank.y);
  ctx.rotate(tank.angle);
  ctx.shadowColor = 'rgba(0,0,0,0.5)';
  ctx.shadowBlur = 15;
  ctx.shadowOffsetY = 5;

  const bodyGrad = ctx.createLinearGradient(-20,-25,20,25);
  const color = tankModels[selectedChar].color;
  bodyGrad.addColorStop(0, color);
  bodyGrad.addColorStop(0.5, shadeColor(color, 20));
  bodyGrad.addColorStop(1, shadeColor(color, -30));
  ctx.fillStyle = bodyGrad;
  ctx.fillRect(-20,-25,40,50);

  ctx.fillStyle = 'rgba(255,255,255,0.2)';
  ctx.fillRect(-18,-23,36,4);
  ctx.fillRect(-18,18,36,4);

  ctx.shadowBlur = 10;
  const cannonGrad = ctx.createLinearGradient(-6,-10,6,-10);
  cannonGrad.addColorStop(0, '#0a4a0a');
  cannonGrad.addColorStop(0.5, '#0d8e0d');
  cannonGrad.addColorStop(1, '#0a4a0a');
  ctx.fillStyle = cannonGrad;
  ctx.fillRect(-6,-10,12,-24);

  ctx.shadowBlur = 0;
  if(tank.canShoot){
    ctx.fillStyle = 'rgba(0,255,100,0.6)';
  } else {
    ctx.fillStyle = 'rgba(255,100,0,0.4)';
  }
  ctx.fillRect(-4,-32,8,3);

  ctx.restore();
  if(!tank.canShoot){
    const model = tankModels[selectedChar];
    const reloadProgress = Math.min(1, (Date.now() - tank.lastShot) / model.reloadTime);
    ctx.save();
    ctx.fillStyle = 'rgba(0,0,0,0.7)';
    ctx.fillRect(tank.x - 25, tank.y + 35, 50, 6);
    const reloadGrad = ctx.createLinearGradient(tank.x - 25, 0, tank.x + 25, 0);
    reloadGrad.addColorStop(0, '#ff3333');
    reloadGrad.addColorStop(0.5, '#ffaa00');
    reloadGrad.addColorStop(1, '#00ff00');
    ctx.fillStyle = reloadGrad;
    ctx.fillRect(tank.x - 25, tank.y + 35, 50 * reloadProgress, 6);
    ctx.strokeStyle = 'rgba(255,255,255,0.5)';
    ctx.lineWidth = 1;
    ctx.strokeRect(tank.x - 25, tank.y + 35, 50, 6);
    ctx.restore();
  }
}

function drawEnemy(e){
  ctx.save();
  ctx.translate(e.x + e.width/2, e.y + e.height/2);
  ctx.rotate(Math.PI);
  ctx.shadowColor = 'rgba(0,0,0,0.6)';
  ctx.shadowBlur = 20;
  ctx.shadowOffsetY = 8;
  let grad = ctx.createRadialGradient(0,0,0,0,0,e.width/1.5);
  if(e.type==="miniBoss"){ 
    grad.addColorStop(0,"#FFD700"); grad.addColorStop(0.5,"#FFA500"); grad.addColorStop(1,"#FF6600"); 
  }
  else if(e.type==="boss"){ 
    grad.addColorStop(0,"#ff00ff"); grad.addColorStop(0.5,"#8800ff"); grad.addColorStop(1,"#440088"); 
  }
  else if(e.type==="fast"){
    grad.addColorStop(0,"#00ff00"); grad.addColorStop(0.5,"#00cc00"); grad.addColorStop(1,"#006600"); 
  }
  else if(e.type==="tank"){
    grad.addColorStop(0,"#ffaa00"); grad.addColorStop(0.5,"#ff6600"); grad.addColorStop(1,"#cc3300"); 
  }
  else { 
    grad.addColorStop(0,"#ff3333"); grad.addColorStop(0.5,"#cc0000"); grad.addColorStop(1,"#660000"); 
  }
  ctx.fillStyle = grad;
  ctx.fillRect(-e.width/2,-e.height/2,e.width,e.height);
  ctx.fillStyle = 'rgba(255,255,255,0.2)';
  ctx.fillRect(-e.width/2 + 5,-e.height/2 + 5,e.width - 10,e.height/4);
  if(e.hp && e.hp > 1){
    ctx.shadowBlur = 0;
    const maxHp = e.type==="boss"?150:(e.type==="miniBoss"?30:3);
    const hpPercent = e.hp/maxHp;
    ctx.fillStyle="rgba(0,0,0,0.7)";
    ctx.fillRect(-e.width/2,-e.height/2-15,e.width,8);
    const hpGrad = ctx.createLinearGradient(-e.width/2,-e.height/2-15,e.width/2,-e.height/2-15);
    if(hpPercent > 0.5){
      hpGrad.addColorStop(0,"#00ff00");
      hpGrad.addColorStop(1,"#88ff00");
    } else if(hpPercent > 0.2){
      hpGrad.addColorStop(0,"#ffaa00");
      hpGrad.addColorStop(1,"#ff8800");
    } else {
      hpGrad.addColorStop(0,"#ff0000");
      hpGrad.addColorStop(1,"#aa0000");
    }
    ctx.fillStyle = hpGrad;
    ctx.fillRect(-e.width/2,-e.height/2-15,e.width*hpPercent,8);
    ctx.strokeStyle = 'rgba(255,255,255,0.5)';
    ctx.lineWidth = 1;
    ctx.strokeRect(-e.width/2,-e.height/2-15,e.width,8);
  }
  ctx.restore();
}
function moveEnemies(){
  for(let i = enemies.length - 1; i >= 0; i--){
    const e = enemies[i]; e.y += e.speed;
    if((e.type==="miniBoss" || e.type==="boss" || e.type==="tank") && e.y > 0) { enemyShoot(e); }
    if(e.y > canvas.height + 100){ enemies.splice(i, 1); }
  }
}
function enemyShoot(e){
  const now = Date.now();
  const shootDelay = e.type==="boss"?800:(e.type==="miniBoss"?1000:1500);
  if(now - e.lastShot > shootDelay){
    const bulletsCount = e.type==="boss"?3:(e.type==="miniBoss"?2:1);
    for(let i=0;i<bulletsCount;i++){
      bullets.push({
        x: e.x + e.width/2 + (i-1)*20,
        y: e.y + e.height,
        dx: (Math.random()-0.5)*2,
        dy: 5 + Math.random(),
        life:200,
        fromEnemy:true
      });
    }
    e.lastShot = now;
  }
}

function update(){
  if(!gameStarted) return;
  frameCount++;
  moveTank();
  for(let i = bullets.length - 1; i >= 0; i--){
    const b = bullets[i]; b.x += b.dx; b.y += b.dy; b.life--;
    if(b.life<=0 || b.y < -10 || b.y > canvas.height+10){ bullets.splice(i,1); }
  }
  moveEnemies();
  enemySpawnTimer++;
  if(enemySpawnTimer > Math.max(30, 60 - level*2)){
    spawnEnemy();
    enemySpawnTimer = 0;
  }
  if(frameCount % 1000 === 0){ spawnMiniBoss(); }
  if(frameCount % 2000 === 0){ spawnBoss(); }
  for(let i = particles.length - 1; i >= 0; i--){
    const p = particles[i]; p.x += p.vx; p.y += p.vy; p.vy += 0.15; p.life--;
    if(p.life <= 0){ particles.splice(i,1); }
  }
  for(let i = explosions.length - 1; i >= 0; i--){
    const exp = explosions[i]; exp.size += exp.maxSize/40; exp.life--;
    if(exp.life <= 0){ explosions.splice(i,1); }
  }
  for(let bi = bullets.length - 1; bi >= 0; bi--){
    const b = bullets[bi];
    if(b.fromEnemy) {
      if(b.x > tank.x-20 && b.x < tank.x+20 && b.y > tank.y-25 && b.y < tank.y+25){
        tank.lives--; bullets.splice(bi,1); createExplosion(b.x, b.y, 25);
        if(tank.lives <= 0){ gameOver(); return; }
      }
      continue;
    }
    for(let ei = enemies.length - 1; ei >= 0; ei--){
      const e = enemies[ei];
      if(b.x > e.x && b.x < e.x + e.width && b.y > e.y && b.y < e.y + e.height){
        e.hp--; bullets.splice(bi,1); createExplosion(b.x, b.y, 20);
        if(e.hp <= 0){
          createExplosion(e.x + e.width/2, e.y + e.height/2, e.width);
          money += e.points || 5;
          score += e.points || 5;
          enemies.splice(ei,1);
        }
        break;
      }
    }
  }
  for(let ei = enemies.length - 1; ei >= 0; ei--){
    const e = enemies[ei];
    if(tank.x > e.x-20 && tank.x < e.x+e.width+20 && tank.y > e.y-25 && tank.y < e.y+e.height+25){
      tank.lives--; createExplosion(e.x + e.width/2, e.y + e.height/2, e.width); enemies.splice(ei,1);
      if(tank.lives <= 0){ gameOver(); return; }
    }
  }
  charOptions.forEach(opt=>{
    const price = parseInt(opt.dataset.price||0);
    if(price>0 && price<=money) opt.classList.remove('locked');
  });
  stars.forEach(s=>{
    s.y += s.speed;
    if(s.y > canvas.height) { s.y = 0; s.x = Math.random() * canvas.width; }
  });
  level = Math.floor(frameCount/1000) + 1;
}

function gameOver(){
  gameStarted = false;
  setTimeout(()=>{
    alert(`💀 GAME OVER!\n\n🎯 Score: ${score}\n💰 Crédits gagnés: ${money}\n🏆 Niveau atteint: ${level}\n\nRetour au hangar...`);
    document.getElementById('menu').style.display='flex';
    document.getElementById('money').textContent = "💰 Crédits: "+money;
  }, 100);
}

function draw(){
  const grad = ctx.createRadialGradient(canvas.width/2, canvas.height/2, 0, canvas.width/2, canvas.height/2, canvas.width);
  grad.addColorStop(0, '#1a1a3e'); grad.addColorStop(0.5, '#0f1419'); grad.addColorStop(1, '#050508');
  ctx.fillStyle = grad; ctx.fillRect(0,0,canvas.width,canvas.height);
  stars.forEach(s=>{
    ctx.fillStyle = `rgba(255,255,255,${s.size/2})`;
    ctx.beginPath(); ctx.arc(s.x, s.y, s.size, 0, Math.PI*2); ctx.fill();
  });
  ctx.strokeStyle = 'rgba(58, 80, 107, 0.15)'; ctx.lineWidth = 1;
  for(let i=0; i<canvas.width; i+=50){
    ctx.beginPath(); ctx.moveTo(i,0); ctx.lineTo(i,canvas.height); ctx.stroke();
  }
  for(let i=0; i<canvas.height; i+=50){
    ctx.beginPath(); ctx.moveTo(0,i); ctx.lineTo(canvas.width,i); ctx.stroke();
  }
  ctx.strokeStyle = 'rgba(0,255,136,0.2)'; ctx.lineWidth = 2; ctx.setLineDash([10, 10]);
  ctx.beginPath(); ctx.moveTo(canvas.width/2, 0); ctx.lineTo(canvas.width/2, canvas.height); ctx.stroke();
  ctx.setLineDash([]);
  explosions.forEach(exp=>{
    const alpha = exp.life/40; ctx.save(); ctx.globalAlpha = alpha;
    const expGrad = ctx.createRadialGradient(exp.x,exp.y,0,exp.x,exp.y,exp.size);
    expGrad.addColorStop(0, 'rgba(255,255,255,1)');
    expGrad.addColorStop(0.2, 'rgba(255,200,100,1)');
    expGrad.addColorStop(0.5, 'rgba(255,100,0,0.8)');
    expGrad.addColorStop(0.8, 'rgba(255,50,0,0.4)');
    expGrad.addColorStop(1, 'rgba(100,0,0,0)');
    ctx.fillStyle = expGrad; ctx.beginPath(); ctx.arc(exp.x, exp.y, exp.size, 0, Math.PI*2); ctx.fill();
    ctx.strokeStyle = `rgba(255,150,0,${alpha})`; ctx.lineWidth = 3;
    ctx.beginPath(); ctx.arc(exp.x, exp.y, exp.size * 1.2, 0, Math.PI*2); ctx.stroke(); ctx.restore();
  });
  drawTank();
  bullets.forEach(b=>{
    ctx.save();
    if(b.fromEnemy){
      ctx.shadowColor = 'rgba(255,100,0,1)'; ctx.shadowBlur = 20;
      const trailGrad = ctx.createLinearGradient(b.x, b.y-10, b.x, b.y+10);
      trailGrad.addColorStop(0, 'rgba(255,150,0,0)'); trailGrad.addColorStop(0.5, 'rgba(255,100,0,0.6)'); trailGrad.addColorStop(1, 'rgba(255,50,0,0)');
      ctx.fillStyle = trailGrad; ctx.fillRect(b.x-4, b.y-15, 8, 20);
      const bulletGrad = ctx.createRadialGradient(b.x, b.y, 0, b.x, b.y, 6);
      bulletGrad.addColorStop(0, '#ffff00'); bulletGrad.addColorStop(0.5, '#ffaa00'); bulletGrad.addColorStop(1, '#ff5500');
      ctx.fillStyle = bulletGrad;
    } else {
      ctx.shadowColor = 'rgba(0,255,255,1)'; ctx.shadowBlur = 20;
      const trailGrad = ctx.createLinearGradient(b.x, b.y+10, b.x, b.y-10);
      trailGrad.addColorStop(0, 'rgba(0,200,255,0)'); trailGrad.addColorStop(0.5, 'rgba(0,255,255,0.6)'); trailGrad.addColorStop(1, 'rgba(100,255,255,0)');
      ctx.fillStyle = trailGrad; ctx.fillRect(b.x-4, b.y-5, 8, 20);
      const bulletGrad = ctx.createRadialGradient(b.x, b.y, 0, b.x, b.y, 6);
      bulletGrad.addColorStop(0, '#ffffff'); bulletGrad.addColorStop(0.5, '#00ffff'); bulletGrad.addColorStop(1, '#0088ff');
      ctx.fillStyle = bulletGrad;
    }
    ctx.beginPath(); ctx.arc(b.x, b.y, 4, 0, Math.PI*2); ctx.fill(); ctx.restore();
  });
  particles.forEach(p=>{
    ctx.save(); const alpha = p.life/p.maxLife; ctx.globalAlpha = alpha;
    const particleGrad = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.size*2);
    particleGrad.addColorStop(0, p.color + '1)'); particleGrad.addColorStop(0.5, p.color + (alpha*0.5) + ')'); particleGrad.addColorStop(1, p.color + '0)');
    ctx.fillStyle = particleGrad; ctx.beginPath(); ctx.arc(p.x, p.y, p.size*2, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = p.color + alpha + ')'; ctx.beginPath(); ctx.arc(p.x, p.y, p.size, 0, Math.PI*2); ctx.fill();
    ctx.restore();
  });
  enemies.forEach(drawEnemy);
  ctx.save();
  const hudGrad = ctx.createLinearGradient(0, 0, 0, 100);
  hudGrad.addColorStop(0, 'rgba(10,10,30,0.9)');
  hudGrad.addColorStop(1, 'rgba(10,10,30,0.7)');
  ctx.fillStyle = hudGrad; ctx.fillRect(5, 5, 250, 90);
  ctx.strokeStyle = '#00ff88'; ctx.lineWidth = 2; ctx.strokeRect(5, 5, 250, 90);
  ctx.fillStyle = '#00ff88';
  ctx.fillRect(5, 5, 15, 3); ctx.fillRect(5, 5, 3, 15); ctx.fillRect(240, 5, 15, 3);
  ctx.fillRect(252, 5, 3, 15); ctx.fillRect(5, 92, 15, 3); ctx.fillRect(5, 80, 3, 15);
  ctx.fillRect(240, 92, 15, 3); ctx.fillRect(252, 80, 3, 15);
  ctx.shadowColor = 'rgba(0,0,0,0.8)'; ctx.shadowBlur = 10; ctx.font = "bold 18px 'Segoe UI'";
  ctx.fillStyle="#ff3366"; ctx.fillText("♥".repeat(Math.max(0, tank.lives)), 20, 30);
  ctx.fillStyle="#00ff88"; ctx.font = "bold 16px 'Segoe UI'"; ctx.fillText("Vies: " + tank.lives, 20, 50);
  ctx.fillStyle="#00ccff"; ctx.fillText("🎯 Score: " + score, 20, 72);
  ctx.fillStyle="#ffd700"; ctx.font = "bold 16px 'Segoe UI'"; ctx.fillText("💰 " + money, 150, 30);
  ctx.fillStyle="#ff6600"; ctx.fillText("👾 x" + enemies.length, 150, 52);
  ctx.fillStyle="#ff00ff"; ctx.fillText("⚔️ Niveau " + level, 150, 74);
  const model = tankModels[selectedChar];
  const reloadPercent = tank.canShoot ? 100 : Math.min(100, ((Date.now() - tank.lastShot) / model.reloadTime) * 100);
  ctx.fillStyle = 'rgba(0,0,0,0.7)'; ctx.fillRect(270, 10, 520, 25);
  const reloadColor = tank.canShoot ? '#00ff00' : '#ff3333';
  const weaponGrad = ctx.createLinearGradient(270, 0, 790, 0);
  weaponGrad.addColorStop(0, reloadColor); weaponGrad.addColorStop(1, reloadColor + '66');
  ctx.fillStyle = weaponGrad; ctx.fillRect(270, 10, 520 * (reloadPercent/100), 25);
  ctx.strokeStyle = '#00ff88'; ctx.lineWidth = 2; ctx.strokeRect(270, 10, 520, 25);
  ctx.fillStyle = '#ffffff'; ctx.font = "bold 14px 'Segoe UI'";
  const weaponText = tank.canShoot ? "🔫 ARME PRÊTE" : "⏳ RECHARGEMENT " + Math.floor(reloadPercent) + "%";
  ctx.fillText(weaponText, 450, 28);
  ctx.restore();
}

function loop(){
  if(!gameStarted) return;
  update();
  draw();
  requestAnimationFrame(loop);
}
</script>
</body>
</html>






