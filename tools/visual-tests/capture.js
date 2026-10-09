import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// 1. Parser de argumentos con nombre (--mode <val>, --only <id>, --current)
function parseArgs() {
  const args = process.argv.slice(2);
  const result = { mode: 'baseline', only: null };
  for (let i = 0; i < args.length; i++) {
    if (args[i] === '--mode' && i + 1 < args.length) {
      result.mode = args[++i];
    } else if (args[i].startsWith('--mode=')) {
      result.mode = args[i].split('=')[1];
    } else if (args[i] === '--current') {
      result.mode = 'current';
    } else if (args[i] === '--only' && i + 1 < args.length) {
      result.only = args[++i];
    } else if (args[i].startsWith('--only=')) {
      result.only = args[i].split('=')[1];
    }
  }
  return result;
}

const parsedArgs = parseArgs();
const MODE = parsedArgs.mode;
const ONLY_ID = parsedArgs.only;

// Cargar CSS original de git 502cad9 si estamos en modo A/B
let originalCssContent = '';
if (MODE === 'ab') {
  try {
    originalCssContent = execSync('git show 502cad9:public/css/style.css', {
      cwd: path.join(__dirname, '../..'),
      encoding: 'utf-8'
    });
    console.log(`[A/B] CSS original cargado desde git 502cad9:public/css/style.css (${originalCssContent.length} bytes).`);
  } catch (e) {
    console.error('❌ Error al obtener CSS original de git:', e.message);
    process.exit(1);
  }
}

// 2. Parser seguro de .env (soporta valores con "=")
function loadEnv(filePath) {
  if (!fs.existsSync(filePath)) return {};
  const lines = fs.readFileSync(filePath, 'utf-8').split('\n');
  const env = {};
  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) continue;
    const idx = trimmed.indexOf('=');
    if (idx === -1) continue;
    const key = trimmed.slice(0, idx).trim();
    let val = trimmed.slice(idx + 1).trim();
    if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
      val = val.slice(1, -1);
    }
    env[key] = val;
  }
  return env;
}

const fileEnv = Object.assign(
  {},
  loadEnv(path.join(__dirname, '../.env')),
  loadEnv(path.join(__dirname, '.env'))
);
const BASE_URL = process.env.BASE_URL || fileEnv.BASE_URL || 'http://localhost/inventario_app';
const USER = process.env.SITIA_TEST_USER || fileEnv.SITIA_TEST_USER;
const PASS = process.env.SITIA_TEST_PASS || fileEnv.SITIA_TEST_PASS;

if (!USER || !PASS) {
  console.error('\n❌ ERROR CRÍTICO: Falta configurar credenciales de prueba.');
  console.error('Debes definir SITIA_TEST_USER y SITIA_TEST_PASS en el entorno o en tools/.env / tools/visual-tests/.env\n');
  process.exit(1);
}

// 3. Conteo de filas de base de datos para verificación de integridad
function getDbCounts() {
  const phpBin = '/opt/lampp/bin/php';
  const phpCode = 'define("APP_INIT", true); require "includes/config.php"; $db = conectarDB(); echo json_encode(["sesiones" => (int)$db->query("SELECT count(*) FROM sesiones")->fetchColumn(), "auditoria" => (int)$db->query("SELECT count(*) FROM auditoria_acciones")->fetchColumn()]);';
  const cmd = `${phpBin} -d display_errors=0 -r '${phpCode}'`;
  try {
    const out = execSync(cmd, { cwd: path.join(__dirname, '../..'), encoding: 'utf-8' });
    const parsed = JSON.parse(out.trim());
    if (typeof parsed.sesiones !== 'number' || typeof parsed.auditoria !== 'number') {
      throw new Error(`Respuesta inválida: ${out}`);
    }
    return parsed;
  } catch (e) {
    console.error(`\n❌ ERROR al consultar conteo de BD: ${e.message}`);
    throw e;
  }
}

// 4. Validación de Git limpio en modo baseline (nunca en smoke ni pruebas)
let currentCommit = 'unknown';
try {
  currentCommit = execSync('git rev-parse HEAD', { cwd: path.join(__dirname, '../..'), encoding: 'utf-8' }).trim();
} catch (e) {}

if (MODE.startsWith('baseline')) {
  try {
    const gitStatus = execSync('git status --porcelain', { cwd: path.join(__dirname, '../..'), encoding: 'utf-8' }).trim();
    const dirty = gitStatus.split('\n').filter(l => l && !l.includes('tools/visual-tests') && !l.includes('refactor-css.md') && !l.includes('.gitignore'));
    if (dirty.length > 0) {
      console.error('\n❌ ERROR: Git tiene archivos modificados pendientes. La línea base requiere git limpio:');
      dirty.forEach(l => console.error(`   ${l}`));
      process.exit(1);
    }
  } catch (e) {
    console.error('Error al verificar estado de Git:', e.message);
  }
}

const VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'desktop_1366', width: 1366, height: 768 },
  { name: 'desktop_1280', width: 1280, height: 720 },
  { name: 'tablet',  width: 768,  height: 1024 },
  { name: 'mobile',  width: 375,  height: 812 }
];

const THEMES = ['light', 'dark'];

// 12 Pantallas fijas con selectores esperados verificados
const ALL_URLS = [
  { id: '01_dashboard', path: '/pages/dashboard.php', expectedSelector: '#content, .dashboard-card' },
  { id: '02_insumos_list', path: '/pages/insumos/listar.php', hasDataTable: true, expectedSelector: 'table.dataTable, .dataTables_wrapper' },
  { id: '03_insumo_ver', path: '/pages/insumos/ver.php?id=3', expectedSelector: '.card, .badge.estado-disponible' },
  { id: '04_insumo_agregar', path: '/pages/insumos/agregar_nueva.php', expectedSelector: '.stepper, form' },
  { id: '05_pedidos_list', path: '/pages/pedidos/listar.php', hasDataTable: true, expectedSelector: 'table.dataTable, .dataTables_wrapper' },
  { id: '06_asignaciones_list', path: '/pages/asignaciones/listar.php', hasDataTable: true, expectedSelector: 'table.dataTable, .dataTables_wrapper' },
  { id: '07_telecom_telefonia', path: '/pages/admin/telecom_telefonia.php', hasDataTable: true, expectedSelector: 'table.dataTable, .dataTables_wrapper' },
  { id: '08_telecom_resumen', path: '/pages/admin/telecom_resumen.php', expectedSelector: 'canvas#chartTiposConexion, .card' },
  { id: '09_admin_usuarios', path: '/pages/admin/usuarios/listar.php', hasDataTable: true, expectedSelector: '#tablaUsuarios, .dataTables_wrapper' },
  { id: '10_auditoria', path: '/pages/admin/auditoria.php', hasDataTable: true, expectedSelector: '#tablaAuditoria, .dataTables_wrapper' },
  { id: '11_movimientos', path: '/pages/insumos/movimientos.php', hasDataTable: true, expectedSelector: 'table.dataTable, .dataTables_wrapper' },
  { id: '12_gallery', path: '/tools/visual-tests/component-gallery.php', expectedSelector: '#gallery_select2, .stepper' }
];

// Filtrado por --only si se especificó
const URLS = ONLY_ID ? ALL_URLS.filter(u => u.id === ONLY_ID || u.id.startsWith(ONLY_ID)) : ALL_URLS;
if (ONLY_ID && URLS.length === 0) {
  console.error(`\n❌ ERROR: No se encontró URL con ID "${ONLY_ID}". Disponibles: ${ALL_URLS.map(u => u.id).join(', ')}`);
  process.exit(1);
}

async function run() {
  console.log(`\n🚀 Iniciando runner de capturas en modo: "${MODE}" ${ONLY_ID ? `(Solo pantalla: ${ONLY_ID})` : ''}`);

  // Registro de conteos antes de iniciar
  const countsBefore = getDbCounts();
  console.log(`[DB ANTES] Sesiones: ${countsBefore.sesiones} | Auditoría: ${countsBefore.auditoria}`);

  const passes = (MODE === 'ab')
    ? [
        { id: 'ab_base', original: true, label: 'Pasada A (CSS Original git 502cad9)' },
        { id: 'ab_current', original: false, label: 'Pasada B (CSS Modular Actual)' }
      ]
    : [
        { id: MODE, original: false, label: `Modo ${MODE}` }
      ];

  let browser = null;

  try {
    browser = await puppeteer.launch({
      executablePath: '/usr/bin/google-chrome',
      headless: 'new',
      args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu', '--hide-scrollbars']
    });

    const chromeVersion = await browser.version();
    const page = await browser.newPage();
    await page.setCacheEnabled(false);

    let serveOriginalCss = false;

    // Intercepción de red para modo A/B y auditoría determinista
    await page.setRequestInterception(true);
    page.on('request', req => {
      const url = req.url();

      if (serveOriginalCss && url.includes('/public/css/')) {
        const match = url.match(/\/public\/css\/([0-9]{2}-[^?#]+)/);
        if (match) {
          const filename = match[1];
          if (filename.startsWith('00-tokens')) {
            req.respond({
              status: 200,
              contentType: 'text/css',
              headers: {
                'Cache-Control': 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma': 'no-cache'
              },
              body: originalCssContent
            });
            return;
          } else {
            req.respond({
              status: 200,
              contentType: 'text/css',
              headers: {
                'Cache-Control': 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma': 'no-cache'
              },
              body: ''
            });
            return;
          }
        }
      }

      if (url.includes('auditoria_list_ssp.php')) {
        const dummyData = [];
        for (let i = 0; i < 25; i++) {
          dummyData.push([
            '01/01/2026 12:00:00',
            'admin (Superadmin)',
            '<span class="badge bg-info">usuarios</span>',
            '<span class="badge bg-primary">LOGIN</span>',
            'Inicio de sesión exitoso en el sistema',
            '<span class="badge bg-success"><i class="fas fa-check"></i> Éxito</span>',
            '127.0.0.1',
            '<button type="button" class="btn btn-sm btn-outline-info btn-ver-detalles"><i class="fas fa-eye"></i></button>'
          ]);
        }
        req.respond({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            draw: 1,
            recordsTotal: 100,
            recordsFiltered: 100,
            data: dummyData
          })
        });
      } else {
        req.continue();
      }
    });

    // Registro de fallos de recursos sin lanzar excepciones en el listener
    const failedResources = [];
    page.on('response', res => {
      const status = res.status();
      const url = res.url();
      if (url.includes('favicon') || url.endsWith('.map') || url.endsWith('.woff') || url.endsWith('.woff2') || url.endsWith('.ttf')) {
        return;
      }
      if (status >= 400 && (url.endsWith('.css') || url.endsWith('.js') || url.includes('/public/'))) {
        failedResources.push({ url, status });
      }
    });

    page.on('pageerror', err => {
      console.error(`[PAGE JS ERROR] ${err.message}`);
    });

    // Inyección global al inicio de cada documento
    await page.evaluateOnNewDocument(() => {
      // 1. Desactivar animaciones de Chart.js
      let _chart = undefined;
      Object.defineProperty(window, 'Chart', {
        configurable: true,
        enumerable: true,
        get() { return _chart; },
        set(cls) {
          if (cls && cls.defaults) {
            cls.defaults.animation = false;
            cls.defaults.animations = false;
            cls.defaults.resizeDelay = 0;
            if (!cls.defaults.transitions) cls.defaults.transitions = {};
            cls.defaults.transitions.resize = { animation: { duration: 0 } };
            cls.defaults.transitions.active = { animation: { duration: 0 } };
          }
          _chart = cls;
        }
      });

      // 2. CSS Anti-animación
      function injectAntiAnim() {
        try {
          if (!document.getElementById('anti-animation-override')) {
            const style = document.createElement('style');
            style.id = 'anti-animation-override';
            style.textContent = `
              *, *::before, *::after, .btn, .form-control, .form-select, .form-check-input {
                transition: none !important;
                animation: none !important;
                caret-color: transparent !important;
              }
              html, body, * {
                scrollbar-width: none !important;
              }
              ::-webkit-scrollbar {
                display: none !important;
              }
              .modal.fade, .modal.fade .modal-dialog {
                transition: none !important;
                transform: none !important;
              }
              .modal-backdrop, .modal-backdrop.fade, .modal-backdrop.show {
                transition: none !important;
              }
            `;
            (document.head || document.documentElement).appendChild(style);
          }
        } catch (e) {}
      }

      injectAntiAnim();
      document.addEventListener('DOMContentLoaded', injectAntiAnim);
    });

    // Login de solo lectura (único POST permitido)
    console.log(`[AUTH] Iniciando sesión en ${BASE_URL} con usuario "${USER}"...`);
    const loginRes = await page.goto(`${BASE_URL}/login.php`, { waitUntil: 'networkidle0' });
    if (loginRes.status() !== 200) {
      throw new Error(`[AUTH ERROR] login.php respondió con código HTTP ${loginRes.status()}`);
    }
    await page.type('input[name="username"]', USER);
    await page.type('input[name="password"]', PASS);
    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForNavigation({ waitUntil: 'networkidle0' })
    ]);

    if (page.url().includes('login.php')) {
      throw new Error('[AUTH ERROR] Falló el login: la URL sigue siendo login.php. Verificá credenciales.');
    }
    console.log('[AUTH] Sesión autenticada.');

    // Bucle de pasadas (1 pasada en modo normal, 2 pasadas en modo A/B)
    for (const currentPass of passes) {
      console.log(`\n=============================================================`);
      console.log(`🎬 Ejecutando: ${currentPass.label} -> captures/${currentPass.id}`);
      console.log(`=============================================================`);

      serveOriginalCss = currentPass.original;
      const currentOutputDir = path.join(__dirname, 'captures', currentPass.id);
      fs.mkdirSync(currentOutputDir, { recursive: true });

      // Limpiar capturas viejas de esta pasada
      fs.readdirSync(currentOutputDir).forEach(f => {
        if (f.endsWith('.png') || f === 'computed-styles.json' || f === 'computed-buttons.json' || f === 'meta.json') {
          fs.unlinkSync(path.join(currentOutputDir, f));
        }
      });

      // Guardar metadata de la corrida
      const meta = {
        mode: currentPass.id,
        label: currentPass.label,
        original_css: currentPass.original,
        commit: currentCommit,
        fecha: new Date().toISOString(),
        chrome: chromeVersion,
        node: process.version,
        usuario_prueba: USER,
        pantalla_unica: ONLY_ID
      };
      fs.writeFileSync(path.join(currentOutputDir, 'meta.json'), JSON.stringify(meta, null, 2));

      const stylesPath = path.join(currentOutputDir, 'computed-styles.json');
      const computedStylesDump = {};

      // Bucle de Capturas de Pantallas
      for (const item of URLS) {
        for (const theme of THEMES) {
          for (const vp of VIEWPORTS) {
            await page.setViewport({ width: vp.width, height: vp.height, deviceScaleFactor: 1 });
            const targetUrl = `${BASE_URL}${item.path}`;

            // Seteo explícito de tema en localStorage
            await page.evaluate((t) => {
              localStorage.setItem('sitia_tema', t);
            }, theme);

            const response = await page.goto(targetUrl, { waitUntil: 'networkidle0' });

            // Verificación de recursos fallidos en el flujo principal
            if (failedResources.length > 0) {
              const errs = failedResources.map(r => `${r.url} (HTTP ${r.status})`).join(', ');
              failedResources.length = 0;
              throw new Error(`[RESOURCE ERROR] Fallaron recursos críticos en ${targetUrl}: ${errs}`);
            }

            if (response.status() !== 200) {
              throw new Error(`[HTTP ERROR] ${targetUrl} respondió con código HTTP ${response.status()}`);
            }
            if (page.url().includes('login.php')) {
              throw new Error(`[AUTH ERROR] Redirigido a login.php al acceder a ${targetUrl}. Sesión perdida.`);
            }

            const html = await page.content();
            for (const errText of ['Fatal error:', 'Warning:', 'Notice:', 'Deprecated:', 'Parse error:']) {
              if (html.includes(errText)) {
                throw new Error(`[PHP ERROR DETECTED] Se detectó "${errText}" en ${targetUrl}`);
              }
            }

            const hasSelector = await page.$(item.expectedSelector);
            if (!hasSelector) {
              throw new Error(`[DOM ERROR] No se encontró el selector esperado "${item.expectedSelector}" en ${targetUrl}`);
            }

            // Verificación de inyección de estilos anti-animación
            const hasAntiAnim = await page.evaluate(() => !!document.getElementById('anti-animation-override'));
            if (!hasAntiAnim) {
              throw new Error(`[ANTI-ANIMATION ERROR] No se aplicó el estilo anti-animación en ${targetUrl}`);
            }

            await page.evaluateHandle('document.fonts.ready');

            // Verificación estricta de tema aplicado
            const appliedTheme = await page.evaluate(() => document.documentElement.getAttribute('data-theme'));
            if (appliedTheme !== theme) {
              throw new Error(`[THEME MISMATCH] En ${targetUrl}: se esperaba "${theme}" pero tiene "${appliedTheme}"`);
            }

            // Espera DataTables si aplica
            if (item.hasDataTable) {
              try {
                await page.waitForFunction(() => {
                  const proc = document.querySelector('.dataTables_processing, .dt-processing');
                  return !proc || proc.style.display === 'none' || window.getComputedStyle(proc).display === 'none';
                }, { timeout: 6000 });
                await page.waitForSelector('table.dataTable tbody tr, table.datatable tbody tr', { timeout: 4000 });
              } catch (e) {
                console.warn(`[DATATABLES TIMEOUT] Advertencia: timeout o tabla vacía en ${targetUrl}`);
              }
            }

            // Enmascaramiento de Último Acceso en usuarios/listar.php con JS tras render de DataTables
            if (item.id === '09_admin_usuarios') {
              await page.evaluate(() => {
                document.querySelectorAll('#tablaUsuarios tbody tr td:nth-child(5)').forEach(td => {
                  td.textContent = '01/01/2026 00:00';
                });
              });
            }

            // Estabilización de fechas dinámicas (date('Y-m-d'))
            if (item.id === '04_insumo_agregar') {
              await page.evaluate(() => {
                const fAdq = document.querySelector('#fecha_adquisicion');
                if (fAdq) fAdq.value = '2026-01-01';
                const fAsig = document.querySelector('#fecha_asignacion');
                if (fAsig) fAsig.value = '2026-01-01';
              });
            }
            await page.evaluate(() => {
              document.querySelectorAll('input[type="date"]').forEach(inp => {
                if (inp.value) inp.value = '2026-01-01';
              });
            });

            // Verificación y estabilización de dibujo en canvas de Chart.js
            if (item.id === '08_telecom_resumen') {
              await page.waitForFunction(() => {
                const c1 = document.querySelector('canvas#chartTiposConexion');
                const c2 = document.querySelector('canvas#chartOperadores');
                if (!c1 || !c2) return false;
                const hasPixels = (c) => {
                  if (c.width === 0 || c.height === 0) return false;
                  const ctx = c.getContext('2d');
                  const data = ctx.getImageData(0, 0, c.width, c.height).data;
                  for (let i = 3; i < data.length; i += 4) {
                    if (data[i] > 0) return true;
                  }
                  return false;
                };
                return hasPixels(c1) && hasPixels(c2);
              }, { timeout: 6000 });
              await page.evaluate(() => {
                if (window.Chart && window.Chart.instances) {
                  Object.values(window.Chart.instances).forEach(inst => {
                    inst.stop();
                    inst.render();
                  });
                }
              });
              await new Promise(r => setTimeout(r, 400));
            }

            const fileName = `${item.id}_${theme}_${vp.name}.png`;
            const filePath = path.join(currentOutputDir, fileName);
            await page.screenshot({ path: filePath, fullPage: true });
            console.log(`[CAPTURA] (${currentPass.id}) Guardada: ${fileName}`);

            // Volcado incremental de estilos computados (en desktop)
            if (vp.name === 'desktop') {
              const styles = await page.evaluate(() => {
                const selectors = ['.btn-primary', '.btn-secondary', '.card-header', '.table thead th', '.sidebar', '.form-control', '.badge.bg-success'];
                const dump = {};
                selectors.forEach(s => {
                  const el = document.querySelector(s);
                  if (el) {
                    const cs = window.getComputedStyle(el);
                    dump[s] = {
                      color: cs.color,
                      backgroundColor: cs.backgroundColor,
                      borderColor: cs.borderColor,
                      fontSize: cs.fontSize,
                      padding: cs.padding,
                      borderRadius: cs.borderRadius,
                      boxShadow: cs.boxShadow
                    };
                  }
                });
                return dump;
              });
              computedStylesDump[`${item.id}_${theme}`] = styles;
              fs.writeFileSync(stylesPath, JSON.stringify(computedStylesDump, null, 2));
            }
          }
        }
      }

      // CAPTURA DE ESTADOS DE INTERACCIÓN EN AMBOS TEMAS (en corrida completa o --only 12_gallery)
      if (!ONLY_ID || ONLY_ID === '12_gallery') {
        console.log(`\n[INTERACCIÓN] (${currentPass.id}) Capturando estados dinámicos en Modo Claro y Oscuro...`);
        const computedButtonsDump = {};
        const computedBadgesDump = {};
        const computedAlertsDump = {};
        const computedModalsDump = {};
        const computedDropdownsDump = {};
        const computedFormsDump = {};
        const computedCardsDump = {};

        for (const theme of THEMES) {
          const gotoWithTheme = async (relPath, vpWidth = 1440, vpHeight = 900) => {
            await page.setViewport({ width: vpWidth, height: vpHeight, deviceScaleFactor: 1 });
            await page.mouse.move(0, 0);
            await page.evaluate((t) => localStorage.setItem('sitia_tema', t), theme);
            const res = await page.goto(`${BASE_URL}${relPath}`, { waitUntil: 'networkidle0' });
            if (res.status() !== 200) throw new Error(`Fallo HTTP ${res.status()} en ${relPath}`);
            await page.evaluateHandle('document.fonts.ready');
            const tApplied = await page.evaluate(() => document.documentElement.getAttribute('data-theme'));
            if (tApplied !== theme) throw new Error(`THEME MISMATCH en estado (${relPath})`);
            await page.evaluate(() => {
              if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
              window.scrollTo(0, 0);
              document.querySelectorAll('.table-responsive').forEach(el => { el.scrollTop = 0; el.scrollLeft = 0; });
            });
          };

          // 1. Sidebar colapsado en Desktop (1440x900)
          await gotoWithTheme('/pages/dashboard.php');
          await page.evaluate(() => document.documentElement.classList.add('sidebar-collapsed'));
          await page.screenshot({ path: path.join(currentOutputDir, `state_sidebar_desktop_collapsed_${theme}.png`), fullPage: false });

          // 2. Sidebar en Tablet (768x1024)
          await gotoWithTheme('/pages/dashboard.php', 768, 1024);
          await page.screenshot({ path: path.join(currentOutputDir, `state_sidebar_tablet_${theme}.png`), fullPage: false });

          // 3. Drawer móvil abierto (375x812) - Falla si no existe toggle
          await gotoWithTheme('/pages/dashboard.php', 375, 812);
          const toggleBtn = await page.$('#btnToggleSidebar');
          if (!toggleBtn) {
            throw new Error('[SIDEBAR TOGGLE ERROR] No se encontró el botón #btnToggleSidebar en navbar.');
          }
          await toggleBtn.click();
          await page.waitForFunction(() => {
            const sb = document.querySelector('.sidebar');
            if (!sb) return false;
            const rect = sb.getBoundingClientRect();
            return rect.left >= 0 && rect.width > 0;
          }, { timeout: 3000 });
          await page.screenshot({ path: path.join(currentOutputDir, `state_sidebar_mobile_open_${theme}.png`), fullPage: false });

          // 4. Modal Visor PDF abierto (en pedidos con tabla renderizada al fondo)
          await gotoWithTheme('/pages/pedidos/listar.php');
          await page.waitForSelector('table.dataTable tbody tr, table.datatable tbody tr', { timeout: 6000 });
          await page.evaluate(() => {
            return new Promise(resolve => {
              const el = document.querySelector('#modalVisorPDF');
              el.classList.remove('fade');
              el.addEventListener('shown.bs.modal', async () => {
                document.querySelectorAll('.modal-backdrop').forEach(b => {
                  b.classList.remove('fade');
                  b.style.transition = 'none';
                  b.style.animation = 'none';
                });
                await document.fonts.ready;
                resolve();
              }, { once: true });
              const m = bootstrap.Modal.getOrCreateInstance(el);
              m.show();
            });
          });
          await new Promise(r => setTimeout(r, 450));
          await page.screenshot({ path: path.join(currentOutputDir, `state_modal_pdf_${theme}.png`), fullPage: false });

          // 5. Modal Confirmación abierto (en pedidos con tabla de fondo)
          await gotoWithTheme('/pages/pedidos/listar.php');
          await page.waitForSelector('table.dataTable tbody tr, table.datatable tbody tr', { timeout: 6000 });
          await page.evaluate(() => {
            return new Promise(resolve => {
              const el = document.querySelector('#modalConfirmacionSITIA');
              el.classList.remove('fade');
              el.addEventListener('shown.bs.modal', async () => {
                document.querySelectorAll('.modal-backdrop').forEach(b => {
                  b.classList.remove('fade');
                  b.style.transition = 'none';
                  b.style.animation = 'none';
                });
                await document.fonts.ready;
                resolve();
              }, { once: true });
              const m = bootstrap.Modal.getOrCreateInstance(el);
              m.show();
            });
          });
          await page.evaluate(() => new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r))));
          await new Promise(r => setTimeout(r, 600));
          await page.screenshot({ path: path.join(currentOutputDir, `state_modal_confirmacion_${theme}.png`), fullPage: false });

          // Medir computed styles de modal de confirmación
          const modalStyles = await page.evaluate(() => {
            const getCS = (sel) => {
              const el = document.querySelector(sel);
              if (!el) return null;
              const cs = window.getComputedStyle(el);
              return {
                backgroundColor: cs.backgroundColor,
                color: cs.color,
                borderTopColor: cs.borderTopColor,
                borderBottomColor: cs.borderBottomColor,
                borderTopWidth: cs.borderTopWidth,
                borderBottomWidth: cs.borderBottomWidth
              };
            };
            return {
              content: getCS('#modalConfirmacionSITIA .modal-content'),
              header: getCS('#modalConfirmacionSITIA .modal-header'),
              body: getCS('#modalConfirmacionSITIA .modal-body'),
              footer: getCS('#modalConfirmacionSITIA .modal-footer')
            };
          });
          computedModalsDump[theme] = modalStyles;

          // 6. Select2 desplegado con scroll determinista (en galería)
          await gotoWithTheme('/tools/visual-tests/component-gallery.php');
          await page.waitForSelector('.select2-container', { timeout: 4000 });
          await page.evaluate(() => {
            window.scrollTo(0, 0);
            const container = document.querySelector('.select2-container');
            container.scrollIntoView({ block: 'center', behavior: 'instant' });
            $('#gallery_select2').select2('open');
          });
          await page.waitForSelector('.select2-container--open', { timeout: 3000 });
          await page.waitForFunction(() => {
            const d = document.querySelector('.select2-dropdown');
            return d && window.getComputedStyle(d).display !== 'none';
          }, { timeout: 3000 });
          await page.evaluate(() => {
            const inp = document.querySelector('.select2-search__field');
            if (inp) {
              inp.style.caretColor = 'transparent';
              inp.style.transition = 'none';
              inp.style.animation = 'none';
            }
          });
          await new Promise(r => setTimeout(r, 200));
          await page.screenshot({ path: path.join(currentOutputDir, `state_select2_open_${theme}.png`), fullPage: false });

          // 7. Tooltip abierto con scroll determinista (en galería)
          await gotoWithTheme('/tools/visual-tests/component-gallery.php');
          await page.waitForSelector('#tooltip_target', { timeout: 4000 });
          await page.evaluate(() => {
            window.scrollTo(0, 0);
            const el = document.querySelector('#tooltip_target');
            el.scrollIntoView({ block: 'center', behavior: 'instant' });
            const tip = bootstrap.Tooltip.getOrCreateInstance(el);
            tip.show();
          });
          await page.waitForSelector('.tooltip.show', { timeout: 3000 });
          await new Promise(r => setTimeout(r, 200));
          await page.screenshot({ path: path.join(currentOutputDir, `state_tooltip_${theme}.png`), fullPage: false });

          // 8. Hover en botón (moviendo mouse antes)
          await gotoWithTheme('/tools/visual-tests/component-gallery.php');
          await page.evaluate(() => document.querySelector('#test_btn_primary').scrollIntoView({ block: 'center' }));
          await page.mouse.move(0, 0);
          await page.hover('#test_btn_primary');
          await page.screenshot({ path: path.join(currentOutputDir, `state_button_hover_${theme}.png`), fullPage: false });

          // 9. Focus con Tab real activando :focus-visible
          await page.mouse.move(0, 0);
          await page.focus('#test_focus_trigger');
          await page.keyboard.press('Tab'); // Salta a #test_btn_outline
          const isFocusVisible = await page.evaluate(() => {
            const el = document.querySelector('#test_btn_outline');
            return el && el === document.activeElement && el.matches(':focus-visible');
          });
          if (!isFocusVisible) {
            console.warn(`[FOCUS ADVERTENCIA] :focus-visible no alteró propiedades medibles en #test_btn_outline (${theme})`);
          }
          await page.screenshot({ path: path.join(currentOutputDir, `state_button_focus_${theme}.png`), fullPage: false });

          // Helper para capturar elemento con foco y su anillo (box-shadow) sin ruido externo
          const captureFocusedElement = async (selector, filename) => {
            await page.evaluate((sel) => document.querySelector(sel).scrollIntoView({ block: 'center', behavior: 'instant' }), selector);
            await page.focus(selector);
            await new Promise(r => setTimeout(r, 100));
            const el = await page.$(selector);
            const box = await el.boundingBox();
            const pad = 12;
            const clip = {
              x: Math.max(0, Math.round(box.x - pad)),
              y: Math.max(0, Math.round(box.y - pad)),
              width: Math.round(box.width + (pad * 2)),
              height: Math.round(box.height + (pad * 2))
            };
            await page.screenshot({ path: path.join(currentOutputDir, filename), clip });
          };

          // 10. Focus en .form-control
          await captureFocusedElement('#test_focus_form_control', `state_focus_form_control_${theme}.png`);

          // 11. Focus en .form-select
          await captureFocusedElement('#test_focus_form_select', `state_focus_form_select_${theme}.png`);

          // 12. Focus en .form-check-input (checkbox)
          await captureFocusedElement('#test_focus_form_check', `state_focus_form_check_${theme}.png`);

          // 13. Focus en .form-check-input (switch)
          await captureFocusedElement('#test_focus_form_switch', `state_focus_form_switch_${theme}.png`);

          // 14. Focus en buscador de DataTables
          await captureFocusedElement('.dataTables_filter input', `state_focus_datatables_search_${theme}.png`);

          // 15. Focus en select de longitud de DataTables
          await captureFocusedElement('.dataTables_length select', `state_focus_datatables_length_${theme}.png`);

          // 16. Matriz de Botones (Paso 2): 34 variantes x 5 estados x tema con CDP
          console.log(`[MATRIZ BOTONES] (${currentPass.id}) Midiendo 34 variantes x 5 estados (${theme})...`);
          const client = await page.target().createCDPSession();
          await client.send('DOM.enable');
          await client.send('CSS.enable');
          const doc = await client.send('DOM.getDocument');

          const BUTTON_VARIANTS = [
            { id: 'primary', selector: '#btn_matrix_primary' },
            { id: 'outline-primary', selector: '#btn_matrix_outline_primary' },
            { id: 'secondary', selector: '#btn_matrix_secondary' },
            { id: 'success', selector: '#btn_matrix_success' },
            { id: 'danger', selector: '#btn_matrix_danger' },
            { id: 'info', selector: '#btn_matrix_info' },
            { id: 'warning', selector: '#btn_matrix_warning' },
            { id: 'soft-primary', selector: '#btn_matrix_soft_primary' },
            { id: 'soft-success', selector: '#btn_matrix_soft_success' },
            { id: 'soft-warning', selector: '#btn_matrix_soft_warning' },
            { id: 'soft-secondary', selector: '#btn_matrix_soft_secondary' },
            { id: 'pastel-brown', selector: '#btn_matrix_pastel_brown' },
            { id: 'colaborativa', selector: '#btn_matrix_colaborativa' },
            { id: 'btn-close', selector: '#btn_matrix_btn_close' },
            { id: 'btn-sm', selector: '#btn_matrix_btn_sm' },
            { id: 'table-btn-group', selector: '#btn_matrix_table_btn_group' },
            { id: 'btn-light', selector: '#btn_matrix_light' },
            { id: 'outline-secondary', selector: '#btn_matrix_outline_secondary' },
            { id: 'outline-success', selector: '#btn_matrix_outline_success' },
            { id: 'outline-danger', selector: '#btn_matrix_outline_danger' },
            { id: 'outline-warning', selector: '#btn_matrix_outline_warning' },
            { id: 'sidebar-toggle', selector: '#btn_matrix_sidebar_toggle' },
            { id: 'primary-in-card-header', selector: '#btn_primary_in_card_header' },
            { id: 'primary-in-th', selector: '#btn_primary_in_th' },
            { id: 'info-text-white', selector: '#btn_matrix_info_text_white' },
            { id: 'light-text-primary', selector: '#btn_matrix_light_text_primary' },
            { id: 'link-text-danger', selector: '#btn_matrix_link_text_danger' },
            { id: 'link-text-muted', selector: '#btn_matrix_link_text_muted' },
            { id: 'outline-danger-border-opacity-25', selector: '#btn_matrix_outline_danger_border_opacity_25' },
            { id: 'secondary-text-muted', selector: '#btn_matrix_secondary_text_muted' },
            { id: 'secondary-text-white', selector: '#btn_matrix_secondary_text_white' },
            { id: 'success-text-white', selector: '#btn_matrix_success_text_white' },
            { id: 'warning-text-dark', selector: '#btn_matrix_warning_text_dark' },
            { id: 'warning-text-white', selector: '#btn_matrix_warning_text_white' }
          ];
          const BUTTON_STATES = ['normal', 'hover', 'active', 'disabled', 'focus-visible'];

          computedButtonsDump[theme] = {};

          for (const variant of BUTTON_VARIANTS) {
            const node = await client.send('DOM.querySelector', { nodeId: doc.root.nodeId, selector: variant.selector });
            if (!node || !node.nodeId) {
              throw new Error(`[MATRIZ ERROR] Selector no encontrado en galería: ${variant.selector}`);
            }
            computedButtonsDump[theme][variant.id] = {};
            for (const state of BUTTON_STATES) {
              const pseudos = state === 'normal' ? [] : [state];
              await client.send('CSS.forcePseudoState', { nodeId: node.nodeId, forcedPseudoClasses: pseudos });
              const styles = await page.evaluate((sel) => {
                const el = document.querySelector(sel);
                if (!el) throw new Error(`[MATRIZ ERROR] Elemento no encontrado en evaluación: ${sel}`);
                const cs = window.getComputedStyle(el);
                return {
                  backgroundColor: cs.backgroundColor,
                  color: cs.color,
                  borderColor: cs.borderColor,
                  boxShadow: cs.boxShadow,
                  outline: cs.outline,
                  opacity: cs.opacity,
                  fontWeight: cs.fontWeight,
                  borderTopWidth: cs.borderTopWidth,
                  borderTopStyle: cs.borderTopStyle,
                  textDecorationLine: cs.textDecorationLine,
                  textShadow: cs.textShadow,
                  filter: cs.filter,
                  backgroundImage: cs.backgroundImage
                };
              }, variant.selector);
              computedButtonsDump[theme][variant.id][state] = styles;
              await client.send('CSS.forcePseudoState', { nodeId: node.nodeId, forcedPseudoClasses: [] });
            }
          }
          await client.detach();

          // 17. Matriz de Badges y Estados (Base + Utilidades): 30 variantes x 2 temas
          console.log(`[MATRIZ BADGES] (${currentPass.id}) Midiendo 30 variantes (${theme})...`);
          const BADGE_VARIANTS = [
            { id: 'primary', selector: '#badge_matrix_primary' },
            { id: 'secondary', selector: '#badge_matrix_secondary' },
            { id: 'success', selector: '#badge_matrix_success' },
            { id: 'danger', selector: '#badge_matrix_danger' },
            { id: 'warning', selector: '#badge_matrix_warning' },
            { id: 'info', selector: '#badge_matrix_info' },
            { id: 'dark', selector: '#badge_matrix_dark' },
            { id: 'badge-tipo', selector: '#badge_matrix_tipo' },
            { id: 'badge-colaborativa', selector: '#badge_matrix_colaborativa' },
            { id: 'estado-disponible', selector: '#badge_matrix_estado_disponible' },
            { id: 'estado-activa', selector: '#badge_matrix_estado_activa' },
            { id: 'estado-asignado', selector: '#badge_matrix_estado_asignado' },
            { id: 'estado-parcial', selector: '#badge_matrix_estado_parcial' },
            { id: 'estado-baja', selector: '#badge_matrix_estado_baja' },
            { id: 'estado-devuelta', selector: '#badge_matrix_estado_devuelta' },
            { id: 'estado-anulado', selector: '#badge_matrix_estado_anulado' },
            // Combinaciones con utilidades auditadas
            { id: 'util_info_text_dark', selector: '#badge_util_info_text_dark' },
            { id: 'util_warning_text_dark', selector: '#badge_util_warning_text_dark' },
            { id: 'util_light_text_dark_border', selector: '#badge_util_light_text_dark_border' },
            { id: 'util_light_text_muted_border', selector: '#badge_util_light_text_muted_border' },
            { id: 'util_light_text_primary', selector: '#badge_util_light_text_primary' },
            { id: 'util_white_text_dark_border', selector: '#badge_util_white_text_dark_border' },
            { id: 'util_primary_text_white', selector: '#badge_util_primary_text_white' },
            { id: 'util_success_text_white', selector: '#badge_util_success_text_white' },
            { id: 'util_primary_subtle', selector: '#badge_util_primary_subtle' },
            { id: 'util_success_subtle', selector: '#badge_util_success_subtle' },
            { id: 'util_warning_subtle', selector: '#badge_util_warning_subtle' },
            { id: 'util_secondary_subtle', selector: '#badge_util_secondary_subtle' },
            { id: 'util_success_opacity_10', selector: '#badge_util_success_opacity_10' },
            { id: 'util_warning_opacity_25', selector: '#badge_util_warning_opacity_25' }
          ];

          computedBadgesDump[theme] = {};
          for (const variant of BADGE_VARIANTS) {
            const styles = await page.evaluate((sel) => {
              const el = document.querySelector(sel);
              if (!el) throw new Error(`[MATRIZ ERROR] Selector de badge no encontrado: ${sel}`);
              const cs = window.getComputedStyle(el);
              return {
                backgroundColor: cs.backgroundColor,
                color: cs.color,
                borderColor: cs.borderColor,
                borderTopWidth: cs.borderTopWidth,
                borderTopStyle: cs.borderTopStyle,
                fontWeight: cs.fontWeight
              };
            }, variant.selector);
            computedBadgesDump[theme][variant.id] = styles;
          }

          // 18. Matriz de Alertas (Base + Utilidades): 10 variantes x 2 temas
          console.log(`[MATRIZ ALERTAS] (${currentPass.id}) Midiendo 10 variantes (${theme})...`);
          const ALERT_VARIANTS = [
            { id: 'success', selector: '#alert_matrix_success' },
            { id: 'warning', selector: '#alert_matrix_warning' },
            { id: 'danger', selector: '#alert_matrix_danger' },
            { id: 'info', selector: '#alert_matrix_info' },
            { id: 'light', selector: '#alert_matrix_light' },
            // Combinaciones con utilidades auditadas
            { id: 'util_warning_border_warning', selector: '#alert_util_warning_border_warning' },
            { id: 'util_warning_border_0', selector: '#alert_util_warning_border_0' },
            { id: 'util_info_border_info', selector: '#alert_util_info_border_info' },
            { id: 'util_light_border', selector: '#alert_util_light_border' },
            { id: 'util_success_text_center', selector: '#alert_util_success_text_center' }
          ];

          computedAlertsDump[theme] = {};
          for (const variant of ALERT_VARIANTS) {
            const styles = await page.evaluate((sel) => {
              const el = document.querySelector(sel);
              if (!el) throw new Error(`[MATRIZ ERROR] Selector de alerta no encontrado: ${sel}`);
              const cs = window.getComputedStyle(el);
              return {
                backgroundColor: cs.backgroundColor,
                color: cs.color,
                borderColor: cs.borderColor,
                borderTopWidth: cs.borderTopWidth,
                borderTopStyle: cs.borderTopStyle
              };
            }, variant.selector);
            computedAlertsDump[theme][variant.id] = styles;
          }

          // 19. Dropdowns: menu, item, divider y combinaciones con utilidades (normal + hover)
          console.log(`[MATRIZ DROPDOWNS] (${currentPass.id}) Midiendo elementos y utilidades (${theme})...`);
          const dropdownStyles = await page.evaluate(() => {
            const menu = document.querySelector('#gallery_dropdown_menu');
            const item = document.querySelector('#gallery_dropdown_item_1');
            const divider = document.querySelector('#gallery_dropdown_divider');
            const itemDanger = document.querySelector('#dropdown_util_item_danger');
            const itemMuted = document.querySelector('#dropdown_util_item_muted');
            if (!menu || !item || !divider || !itemDanger || !itemMuted) throw new Error('[DROPDOWNS ERROR] Elementos no encontrados en galería');
            const csMenu = window.getComputedStyle(menu);
            const csItem = window.getComputedStyle(item);
            const csDivider = window.getComputedStyle(divider);
            const csDanger = window.getComputedStyle(itemDanger);
            const csMuted = window.getComputedStyle(itemMuted);
            return {
              menu: {
                backgroundColor: csMenu.backgroundColor,
                borderColor: csMenu.borderColor,
                borderTopWidth: csMenu.borderTopWidth,
                borderTopStyle: csMenu.borderTopStyle
              },
              item: {
                color: csItem.color,
                backgroundColor: csItem.backgroundColor
              },
              divider: {
                borderTopColor: csDivider.borderTopColor,
                borderTopWidth: csDivider.borderTopWidth,
                borderTopStyle: csDivider.borderTopStyle
              },
              item_danger: {
                color: csDanger.color,
                backgroundColor: csDanger.backgroundColor
              },
              item_muted: {
                color: csMuted.color,
                backgroundColor: csMuted.backgroundColor
              }
            };
          });

          // Medir hover en dropdown items con utilidades vía CDP
          const clientDrop = await page.target().createCDPSession();
          await clientDrop.send('DOM.enable');
          await clientDrop.send('CSS.enable');
          const docDrop = await clientDrop.send('DOM.getDocument');

          const getHoverCS = async (sel) => {
            const node = await clientDrop.send('DOM.querySelector', { nodeId: docDrop.root.nodeId, selector: sel });
            await clientDrop.send('CSS.forcePseudoState', { nodeId: node.nodeId, forcedPseudoClasses: ['hover'] });
            const cs = await page.evaluate((s) => {
              const el = document.querySelector(s);
              const st = window.getComputedStyle(el);
              return { color: st.color, backgroundColor: st.backgroundColor };
            }, sel);
            await clientDrop.send('CSS.forcePseudoState', { nodeId: node.nodeId, forcedPseudoClasses: [] });
            return cs;
          };

          dropdownStyles.item_danger_hover = await getHoverCS('#dropdown_util_item_danger');
          dropdownStyles.item_muted_hover = await getHoverCS('#dropdown_util_item_muted');
          await clientDrop.detach();

          computedDropdownsDump[theme] = dropdownStyles;

          // 20. Formularios e Inputs: disabled, readonly, input-group-text, file-selector-button
          console.log(`[MATRIZ FORMULARIOS] (${currentPass.id}) Midiendo elementos (${theme})...`);
          const formsStyles = await page.evaluate(() => {
            const inputDisabled = document.querySelector('#gallery_input_disabled');
            const selectDisabled = document.querySelector('#gallery_select_disabled');
            const inputReadonly = document.querySelector('#gallery_input_readonly');
            const inputGroupText = document.querySelector('#gallery_input_group_text');
            const fileInput = document.querySelector('#gallery_file_input');

            if (!inputDisabled || !selectDisabled || !inputReadonly || !inputGroupText || !fileInput) {
              throw new Error('[FORMULARIOS ERROR] Elementos no encontrados en galería');
            }

            const csInputDis = window.getComputedStyle(inputDisabled);
            const csSelectDis = window.getComputedStyle(selectDisabled);
            const csInputRo = window.getComputedStyle(inputReadonly);
            const csIgText = window.getComputedStyle(inputGroupText);
            const csFileBtn = window.getComputedStyle(fileInput, '::file-selector-button');

            return {
              input_disabled: {
                backgroundColor: csInputDis.backgroundColor,
                color: csInputDis.color,
                opacity: csInputDis.opacity
              },
              select_disabled: {
                backgroundColor: csSelectDis.backgroundColor,
                color: csSelectDis.color,
                opacity: csSelectDis.opacity
              },
              input_readonly: {
                backgroundColor: csInputRo.backgroundColor,
                color: csInputRo.color,
                opacity: csInputRo.opacity
              },
              input_group_text: {
                backgroundColor: csIgText.backgroundColor,
                color: csIgText.color,
                borderColor: csIgText.borderColor
              },
              file_button: {
                backgroundColor: csFileBtn.backgroundColor,
                color: csFileBtn.color,
                borderColor: csFileBtn.borderColor
              }
            };
          });
          computedFormsDump[theme] = formsStyles;

          // 21. Tarjetas temáticas y KPI (Dashboard)
          console.log(`[MATRIZ TARJETAS Y KPI] (${currentPass.id}) Midiendo elementos (${theme})...`);
          const cardsStyles = await page.evaluate(() => {
            const hSuccess = document.querySelector('#gallery_header_success');
            const hInfo = document.querySelector('#gallery_header_info');
            const hWarning = document.querySelector('#gallery_header_warning');
            const hDanger = document.querySelector('#gallery_header_danger');

            const kBase = document.querySelector('#gallery_kpi_base');
            const kPrimary = document.querySelector('#gallery_kpi_primary');
            const kSuccess = document.querySelector('#gallery_kpi_success');
            const kInfo = document.querySelector('#gallery_kpi_info');
            const kWarning = document.querySelector('#gallery_kpi_warning');
            const kDanger = document.querySelector('#gallery_kpi_danger');

            const hUtilPri = document.querySelector('#header_util_primary_text_white');
            const hUtilSuc = document.querySelector('#header_util_success_text_white');
            const hUtilWar = document.querySelector('#header_util_warning_text_dark');
            const hUtilWarOp = document.querySelector('#header_util_warning_opacity_10');
            const hUtilWarSub = document.querySelector('#header_util_warning_subtle');
            const hUtilSucSub = document.querySelector('#header_util_success_subtle');
            const hUtilBgLight = document.querySelector('#header_util_bg_light');
            const hUtilBgWhite = document.querySelector('#header_util_bg_white');
            const hUtilBodyBorder = document.querySelector('#header_util_body_border_bottom');
            const hUtilBorder0Trans = document.querySelector('#header_util_border_0_transparent');
            const kUtilH100 = document.querySelector('#kpi_util_h100');
            const kUtilGrad = document.querySelector('#kpi_util_gradient');

            if (!hSuccess || !hInfo || !hWarning || !hDanger || !kBase || !kPrimary || !kSuccess || !kInfo || !kWarning || !kDanger ||
                !hUtilPri || !hUtilSuc || !hUtilWar || !hUtilWarOp || !hUtilWarSub || !hUtilSucSub || !hUtilBgLight || !hUtilBgWhite || !hUtilBodyBorder || !hUtilBorder0Trans ||
                !kUtilH100 || !kUtilGrad) {
              throw new Error('[TARJETAS Y KPI ERROR] Elementos no encontrados en galería');
            }

            const getHeaderStyle = (el) => {
              const cs = window.getComputedStyle(el);
              return {
                backgroundColor: cs.backgroundColor,
                color: cs.color,
                borderBottomColor: cs.borderBottomColor
              };
            };

            const getKpiStyle = (el) => {
              const cs = window.getComputedStyle(el);
              return {
                backgroundColor: cs.backgroundColor,
                color: cs.color,
                borderLeftColor: cs.borderLeftColor,
                borderLeftWidth: cs.borderLeftWidth
              };
            };

            return {
              header_success: getHeaderStyle(hSuccess),
              header_info: getHeaderStyle(hInfo),
              header_warning: getHeaderStyle(hWarning),
              header_danger: getHeaderStyle(hDanger),
              header_util_primary: getHeaderStyle(hUtilPri),
              header_util_success: getHeaderStyle(hUtilSuc),
              header_util_warning: getHeaderStyle(hUtilWar),
              header_util_warning_opacity_10: getHeaderStyle(hUtilWarOp),
              header_util_warning_subtle: getHeaderStyle(hUtilWarSub),
              header_util_success_subtle: getHeaderStyle(hUtilSucSub),
              header_util_bg_light: getHeaderStyle(hUtilBgLight),
              header_util_bg_white: getHeaderStyle(hUtilBgWhite),
              header_util_body_border: getHeaderStyle(hUtilBodyBorder),
              header_util_border_0_transparent: getHeaderStyle(hUtilBorder0Trans),
              kpi_base: getKpiStyle(kBase),
              kpi_primary: getKpiStyle(kPrimary),
              kpi_success: getKpiStyle(kSuccess),
              kpi_info: getKpiStyle(kInfo),
              kpi_warning: getKpiStyle(kWarning),
              kpi_danger: getKpiStyle(kDanger),
              kpi_util_h100: getKpiStyle(kUtilH100),
              kpi_util_gradient: getKpiStyle(kUtilGrad)
            };
          });
          computedCardsDump[theme] = cardsStyles;

          // 22. Medición de partes de modal con utilidades en galería
          const modalUtilStyles = await page.evaluate(() => {
            const getCS = (sel) => {
              const el = document.querySelector(sel);
              if (!el) return null;
              const cs = window.getComputedStyle(el);
              return {
                backgroundColor: cs.backgroundColor,
                color: cs.color,
                borderTopColor: cs.borderTopColor,
                borderBottomColor: cs.borderBottomColor,
                borderTopWidth: cs.borderTopWidth,
                borderBottomWidth: cs.borderBottomWidth
              };
            };
            return {
              header_util_primary: getCS('#modal_util_header_primary'),
              header_util_success: getCS('#modal_util_header_success'),
              header_util_danger: getCS('#modal_util_header_danger'),
              header_util_warning: getCS('#modal_util_header_warning'),
              header_util_light: getCS('#modal_util_header_light'),
              header_util_warning_subtle: getCS('#modal_util_header_warning_subtle'),
              content_util_border_0: getCS('#modal_util_content_border_0'),
              body_util_center: getCS('#modal_util_body_center'),
              footer_util_light: getCS('#modal_util_footer_light'),
              footer_util_border_0: getCS('#modal_util_footer_border_0'),
              title_util_success: getCS('#modal_util_title_success')
            };
          });
          computedModalsDump[theme] = { ...(computedModalsDump[theme] || {}), ...modalUtilStyles };
        }

        fs.writeFileSync(path.join(currentOutputDir, 'computed-buttons.json'), JSON.stringify(computedButtonsDump, null, 2));
        fs.writeFileSync(path.join(currentOutputDir, 'computed-badges.json'), JSON.stringify(computedBadgesDump, null, 2));
        fs.writeFileSync(path.join(currentOutputDir, 'computed-alerts.json'), JSON.stringify(computedAlertsDump, null, 2));
        fs.writeFileSync(path.join(currentOutputDir, 'computed-modals.json'), JSON.stringify(computedModalsDump, null, 2));
        fs.writeFileSync(path.join(currentOutputDir, 'computed-dropdowns.json'), JSON.stringify(computedDropdownsDump, null, 2));
        fs.writeFileSync(path.join(currentOutputDir, 'computed-forms.json'), JSON.stringify(computedFormsDump, null, 2));
        fs.writeFileSync(path.join(currentOutputDir, 'computed-cards.json'), JSON.stringify(computedCardsDump, null, 2));
      }
    } // Fin bucle de pasadas

  } finally {
    if (browser) {
      await browser.close();
      console.log('[BROWSER] Instancia de Chrome cerrada limpiamente.');
    }
  }

  // Verificación de integridad en la base de datos (+1 en sesiones y auditoría)
  const countsAfter = getDbCounts();
  const diffSesiones = countsAfter.sesiones - countsBefore.sesiones;
  const diffAuditoria = countsAfter.auditoria - countsBefore.auditoria;

  console.log(`\n[DB DESPUÉS] Sesiones: ${countsAfter.sesiones} | Auditoría: ${countsAfter.auditoria}`);
  console.log(`[DB DELTA] Sesiones: +${diffSesiones} | Auditoría: +${diffAuditoria}`);

  if (diffSesiones < 1 || diffAuditoria < 1) {
    throw new Error(`[DB INTEGRITY ERROR] No se registró la sesión de prueba en la base de datos: Sesiones +${diffSesiones}, Auditoría +${diffAuditoria}`);
  }

  console.log(`\n✅ Proceso completado exitosamente en modo: "${MODE}"`);
}

run().catch(err => {
  console.error('\n❌ [ERROR FATAL]', err.message);
  process.exit(1);
});
