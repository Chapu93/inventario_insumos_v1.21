import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
let BASELINE_DIR = path.join(__dirname, 'captures', 'baseline');
let TARGET_DIR = path.join(__dirname, 'captures', 'current');

if (process.argv[2] === '--mode' && process.argv[3] === 'ab') {
  BASELINE_DIR = path.join(__dirname, 'captures', 'ab_base');
  TARGET_DIR = path.join(__dirname, 'captures', 'ab_current');
} else if (process.argv[2] === 'ab') {
  BASELINE_DIR = path.join(__dirname, 'captures', 'ab_base');
  TARGET_DIR = path.join(__dirname, 'captures', 'ab_current');
} else {
  if (process.argv[2]) BASELINE_DIR = path.resolve(process.argv[2]);
  if (process.argv[3]) TARGET_DIR = path.resolve(process.argv[3]);
}
const DIFF_DIR = path.join(__dirname, 'captures', 'diff');

// Limpiar carpeta de diffs al inicio de la corrida
if (fs.existsSync(DIFF_DIR)) {
  fs.rmSync(DIFF_DIR, { recursive: true, force: true });
}
fs.mkdirSync(DIFF_DIR, { recursive: true });

if (!fs.existsSync(BASELINE_DIR)) {
  console.error(`\n❌ ERROR: No existe el directorio base: ${BASELINE_DIR}`);
  process.exit(1);
}
if (!fs.existsSync(TARGET_DIR)) {
  console.error(`\n❌ ERROR: No existe el directorio destino: ${TARGET_DIR}`);
  process.exit(1);
}

const files = fs.readdirSync(BASELINE_DIR).filter(f => f.endsWith('.png'));
console.log(`\n🔍 Comparando capturas pixel a pixel (Threshold: 0, includeAA: true):`);
console.log(`  Base:   ${BASELINE_DIR}`);
console.log(`  Actual: ${TARGET_DIR}\n`);

// Alerta de capturas extra en la carpeta actual
const targetFiles = fs.readdirSync(TARGET_DIR).filter(f => f.endsWith('.png'));
const extraFiles = targetFiles.filter(f => !files.includes(f));
if (extraFiles.length > 0) {
  console.warn(`⚠️ [ARCHIVOS EXTRA] Existen ${extraFiles.length} capturas en ${TARGET_DIR} que no están en la línea base:`);
  extraFiles.forEach(f => console.warn(`   + ${f}`));
}

// Comparación de metadatos (Commit y Versión de Chrome)
const meta1Path = path.join(BASELINE_DIR, 'meta.json');
const meta2Path = path.join(TARGET_DIR, 'meta.json');
if (fs.existsSync(meta1Path) && fs.existsSync(meta2Path)) {
  const m1 = JSON.parse(fs.readFileSync(meta1Path, 'utf-8'));
  const m2 = JSON.parse(fs.readFileSync(meta2Path, 'utf-8'));
  if (m1.chrome !== m2.chrome) {
    console.warn(`⚠️ [CHROME MISMATCH] Línea base: ${m1.chrome} vs Corrida actual: ${m2.chrome}`);
  }
  if (m1.commit !== m2.commit) {
    console.warn(`ℹ️ [GIT COMMIT] Línea base: ${m1.commit?.slice(0, 7)} -> Corrida actual: ${m2.commit?.slice(0, 7)}`);
  }
}

let totalDiffPixels = 0;
let hasCriticalErrors = false;
const results = [];

for (const file of files) {
  const img1Path = path.join(BASELINE_DIR, file);
  const img2Path = path.join(TARGET_DIR, file);

  if (!fs.existsSync(img2Path)) {
    results.push({ archivo: file, píxeles: 'N/A', diff_pct: 'N/A', estado: 'FALTA_CAPTURA' });
    hasCriticalErrors = true;
    continue;
  }

  const img1 = PNG.sync.read(fs.readFileSync(img1Path));
  const img2 = PNG.sync.read(fs.readFileSync(img2Path));

  // Manejo estricto de discrepancia de dimensiones sin crash
  if (img1.width !== img2.width || img1.height !== img2.height) {
    results.push({
      archivo: file,
      píxeles: 'N/A',
      diff_pct: '100% (ERROR DIMENSIÓN)',
      estado: 'DIMENSION_MISMATCH',
      detalle: `Base: ${img1.width}x${img1.height}px vs Actual: ${img2.width}x${img2.height}px`
    });
    totalDiffPixels += (img1.width * img1.height);
    hasCriticalErrors = true;
    continue;
  }

  const { width, height } = img1;
  const diff = new PNG({ width, height });

  // Comparación 100% exacta con antialiasing incluido
  const numDiffPixels = pixelmatch(img1.data, img2.data, diff.data, width, height, {
    threshold: 0,
    includeAA: true,
    diffColor: [255, 0, 128] // Resaltado magenta
  });

  const totalPixels = width * height;
  const diffPercent = ((numDiffPixels / totalPixels) * 100).toFixed(4);
  totalDiffPixels += numDiffPixels;

  if (numDiffPixels > 0) {
    fs.writeFileSync(path.join(DIFF_DIR, file), PNG.sync.write(diff));
  }

  results.push({
    archivo: file,
    píxeles: numDiffPixels,
    diff_pct: `${diffPercent}%`,
    estado: numDiffPixels === 0 ? 'IDENTICO' : 'DIFERENCIA'
  });
}

console.table(results);

// Comparación estricta de computed-styles.json
const styles1Path = path.join(BASELINE_DIR, 'computed-styles.json');
const styles2Path = path.join(TARGET_DIR, 'computed-styles.json');
const styleDiffs = [];

if (fs.existsSync(styles1Path) && fs.existsSync(styles2Path)) {
  const s1 = JSON.parse(fs.readFileSync(styles1Path, 'utf-8'));
  const s2 = JSON.parse(fs.readFileSync(styles2Path, 'utf-8'));

  // Detectar páginas o selectores ausentes o alterados
  for (const pageKey of Object.keys(s1)) {
    if (!s2[pageKey]) {
      styleDiffs.push({ página: pageKey, selector: '*', propiedad: 'PAGE_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
      continue;
    }
    for (const sel of Object.keys(s1[pageKey])) {
      if (!s2[pageKey][sel]) {
        styleDiffs.push({ página: pageKey, selector: sel, propiedad: 'SELECTOR_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
        continue;
      }
      const p1 = s1[pageKey][sel];
      const p2 = s2[pageKey][sel];
      for (const prop of Object.keys(p1)) {
        if (p1[prop] !== p2[prop]) {
          styleDiffs.push({
            página: pageKey,
            selector: sel,
            propiedad: prop,
            base: p1[prop],
            actual: p2[prop]
          });
        }
      }
    }
  }

  // Detectar selectores nuevos en la corrida actual que no estaban en la base
  for (const pageKey of Object.keys(s2)) {
    if (!s1[pageKey]) continue;
    for (const sel of Object.keys(s2[pageKey])) {
      if (!s1[pageKey][sel]) {
        styleDiffs.push({ página: pageKey, selector: sel, propiedad: 'SELECTOR_EXTRA', base: 'AUSENTE', actual: 'PRESENTE' });
      }
    }
  }
}

if (styleDiffs.length > 0) {
  console.log('\n⚠️ [COMPUTED STYLES DIFF] Discrepancias en propiedades calculadas:');
  console.table(styleDiffs);
  fs.writeFileSync(path.join(DIFF_DIR, 'computed-styles-diff.json'), JSON.stringify(styleDiffs, null, 2));
} else {
  console.log('\n✅ [COMPUTED STYLES] Estilos calculados 100% idénticos.');
}

// Comparación estricta de computed-buttons.json (Matriz de botones)
const buttons1Path = path.join(BASELINE_DIR, 'computed-buttons.json');
const buttons2Path = path.join(TARGET_DIR, 'computed-buttons.json');
const buttonDiffs = [];
let b1 = null;
let b2 = null;

if (fs.existsSync(buttons1Path) && fs.existsSync(buttons2Path)) {
  b1 = JSON.parse(fs.readFileSync(buttons1Path, 'utf-8'));
  b2 = JSON.parse(fs.readFileSync(buttons2Path, 'utf-8'));

  for (const theme of Object.keys(b1)) {
    if (!b2[theme]) {
      buttonDiffs.push({ tema: theme, variante: '*', estado: '*', propiedad: 'THEME_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
      continue;
    }
    for (const variant of Object.keys(b1[theme])) {
      if (!b2[theme][variant]) {
        buttonDiffs.push({ tema: theme, variante: variant, estado: '*', propiedad: 'VARIANT_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
        continue;
      }
      for (const state of Object.keys(b1[theme][variant])) {
        if (!b2[theme][variant][state]) {
          buttonDiffs.push({ tema: theme, variante: variant, estado: state, propiedad: 'STATE_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
          continue;
        }
        const props1 = b1[theme][variant][state];
        const props2 = b2[theme][variant][state];
        for (const prop of [
          'backgroundColor', 'color', 'borderColor', 'boxShadow', 'outline', 'opacity',
          'fontWeight', 'borderTopWidth', 'borderTopStyle', 'textDecorationLine',
          'textShadow', 'filter', 'backgroundImage'
        ]) {
          if (props1[prop] !== props2[prop]) {
            buttonDiffs.push({
              tema: theme,
              variante: variant,
              estado: state,
              propiedad: prop,
              base: props1[prop],
              actual: props2[prop]
            });
          }
        }
      }
    }
  }

  // Detectar variantes extras en la corrida actual
  for (const theme of Object.keys(b2)) {
    if (!b1[theme]) continue;
    for (const variant of Object.keys(b2[theme])) {
      if (!b1[theme][variant]) {
        buttonDiffs.push({ tema: theme, variante: variant, estado: '*', propiedad: 'VARIANT_EXTRA', base: 'AUSENTE', actual: 'PRESENTE' });
      }
    }
  }
} else if (fs.existsSync(buttons2Path) && !fs.existsSync(buttons1Path)) {
  console.warn('⚠️ [MATRIZ DE BOTONES] computed-buttons.json presente en actual pero ausente en base.');
}

if (buttonDiffs.length > 0) {
  console.log('\n⚠️ [MATRIZ DE BOTONES DIFF] Discrepancias en matriz de botones:');
  console.table(buttonDiffs);
  fs.writeFileSync(path.join(DIFF_DIR, 'computed-buttons-diff.json'), JSON.stringify(buttonDiffs, null, 2));
} else if (fs.existsSync(buttons1Path) && fs.existsSync(buttons2Path)) {
  const totalVariants = Object.keys(b1.light || {}).length;
  console.log(`\n✅ [MATRIZ DE BOTONES] ${totalVariants} variantes x 5 estados x 2 temas 100% idénticos (0 diferencias).`);
}

// Comparación estricta de computed-badges.json (Matriz de badges y estados)
const badges1Path = path.join(BASELINE_DIR, 'computed-badges.json');
const badges2Path = path.join(TARGET_DIR, 'computed-badges.json');
const badgeDiffs = [];
let bg1 = null;
let bg2 = null;

if (fs.existsSync(badges1Path) && fs.existsSync(badges2Path)) {
  bg1 = JSON.parse(fs.readFileSync(badges1Path, 'utf-8'));
  bg2 = JSON.parse(fs.readFileSync(badges2Path, 'utf-8'));

  for (const theme of Object.keys(bg1)) {
    if (!bg2[theme]) {
      badgeDiffs.push({ tema: theme, variante: '*', propiedad: 'THEME_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
      continue;
    }
    for (const variant of Object.keys(bg1[theme])) {
      if (!bg2[theme][variant]) {
        badgeDiffs.push({ tema: theme, variante: variant, propiedad: 'VARIANT_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
        continue;
      }
      const props1 = bg1[theme][variant];
      const props2 = bg2[theme][variant];
      for (const prop of ['backgroundColor', 'color', 'borderColor', 'borderTopWidth', 'borderTopStyle', 'fontWeight']) {
        if (props1[prop] !== props2[prop]) {
          badgeDiffs.push({
            tema: theme,
            variante: variant,
            propiedad: prop,
            base: props1[prop],
            actual: props2[prop]
          });
        }
      }
    }
  }

  for (const theme of Object.keys(bg2)) {
    if (!bg1[theme]) continue;
    for (const variant of Object.keys(bg2[theme])) {
      if (!bg1[theme][variant]) {
        badgeDiffs.push({ tema: theme, variante: variant, propiedad: 'VARIANT_EXTRA', base: 'AUSENTE', actual: 'PRESENTE' });
      }
    }
  }
} else if (fs.existsSync(badges2Path) && !fs.existsSync(badges1Path)) {
  console.warn('⚠️ [MATRIZ DE BADGES] computed-badges.json presente en actual pero ausente en base.');
}

if (badgeDiffs.length > 0) {
  console.log('\n⚠️ [MATRIZ DE BADGES DIFF] Discrepancias en matriz de badges:');
  console.table(badgeDiffs);
  fs.writeFileSync(path.join(DIFF_DIR, 'computed-badges-diff.json'), JSON.stringify(badgeDiffs, null, 2));
} else if (fs.existsSync(badges1Path) && fs.existsSync(badges2Path)) {
  const totalBadgeVariants = Object.keys(bg1.light || {}).length;
  console.log(`\n✅ [MATRIZ DE BADGES] ${totalBadgeVariants} variantes x 2 temas 100% idénticos (0 diferencias).`);
}

// Comparación estricta de computed-alerts.json (Matriz de alertas)
const alerts1Path = path.join(BASELINE_DIR, 'computed-alerts.json');
const alerts2Path = path.join(TARGET_DIR, 'computed-alerts.json');
const alertDiffs = [];
let al1 = null;
let al2 = null;

if (fs.existsSync(alerts1Path) && fs.existsSync(alerts2Path)) {
  al1 = JSON.parse(fs.readFileSync(alerts1Path, 'utf-8'));
  al2 = JSON.parse(fs.readFileSync(alerts2Path, 'utf-8'));

  for (const theme of Object.keys(al1)) {
    if (!al2[theme]) {
      alertDiffs.push({ tema: theme, variante: '*', propiedad: 'THEME_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
      continue;
    }
    for (const variant of Object.keys(al1[theme])) {
      if (!al2[theme][variant]) {
        alertDiffs.push({ tema: theme, variante: variant, propiedad: 'VARIANT_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
        continue;
      }
      const props1 = al1[theme][variant];
      const props2 = al2[theme][variant];
      for (const prop of ['backgroundColor', 'color', 'borderColor', 'borderTopWidth', 'borderTopStyle']) {
        if (props1[prop] !== props2[prop]) {
          alertDiffs.push({
            tema: theme,
            variante: variant,
            propiedad: prop,
            base: props1[prop],
            actual: props2[prop]
          });
        }
      }
    }
  }

  for (const theme of Object.keys(al2)) {
    if (!al1[theme]) continue;
    for (const variant of Object.keys(al2[theme])) {
      if (!al1[theme][variant]) {
        alertDiffs.push({ tema: theme, variante: variant, propiedad: 'VARIANT_EXTRA', base: 'AUSENTE', actual: 'PRESENTE' });
      }
    }
  }
} else if (fs.existsSync(alerts2Path) && !fs.existsSync(alerts1Path)) {
  console.warn('⚠️ [MATRIZ DE ALERTAS] computed-alerts.json presente en actual pero ausente en base.');
}

if (alertDiffs.length > 0) {
  console.log('\n⚠️ [MATRIZ DE ALERTAS DIFF] Discrepancias en matriz de alertas:');
  console.table(alertDiffs);
  fs.writeFileSync(path.join(DIFF_DIR, 'computed-alerts-diff.json'), JSON.stringify(alertDiffs, null, 2));
} else if (fs.existsSync(alerts1Path) && fs.existsSync(alerts2Path)) {
  const totalAlertVariants = Object.keys(al1.light || {}).length;
  console.log(`\n✅ [MATRIZ DE ALERTAS] ${totalAlertVariants} variantes x 2 temas 100% idénticos (0 diferencias).`);
}

// Comparación estricta de computed-modals.json (Modales)
const modals1Path = path.join(BASELINE_DIR, 'computed-modals.json');
const modals2Path = path.join(TARGET_DIR, 'computed-modals.json');
const modalDiffs = [];
let mod1 = null;
let mod2 = null;

if (fs.existsSync(modals1Path) && fs.existsSync(modals2Path)) {
  mod1 = JSON.parse(fs.readFileSync(modals1Path, 'utf-8'));
  mod2 = JSON.parse(fs.readFileSync(modals2Path, 'utf-8'));

  for (const theme of Object.keys(mod1)) {
    if (!mod2[theme]) {
      modalDiffs.push({ tema: theme, parte: '*', propiedad: 'THEME_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
      continue;
    }
    for (const part of Object.keys(mod1[theme])) {
      if (!mod2[theme][part]) {
        modalDiffs.push({ tema: theme, parte: part, propiedad: 'PART_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
        continue;
      }
      const props1 = mod1[theme][part];
      const props2 = mod2[theme][part];
      for (const prop of ['backgroundColor', 'color', 'borderTopColor', 'borderBottomColor', 'borderTopWidth', 'borderBottomWidth']) {
        if (props1 && props2 && props1[prop] !== props2[prop]) {
          modalDiffs.push({
            tema: theme,
            parte: part,
            propiedad: prop,
            base: props1[prop],
            actual: props2[prop]
          });
        }
      }
    }
  }

  for (const theme of Object.keys(mod2)) {
    if (!mod1[theme]) continue;
    for (const part of Object.keys(mod2[theme])) {
      if (!mod1[theme][part]) {
        modalDiffs.push({ tema: theme, parte: part, propiedad: 'PART_EXTRA', base: 'AUSENTE', actual: 'PRESENTE' });
      }
    }
  }
} else if (fs.existsSync(modals2Path) && !fs.existsSync(modals1Path)) {
  console.warn('⚠️ [MODALES] computed-modals.json presente en actual pero ausente en base.');
}

if (modalDiffs.length > 0) {
  console.log('\n⚠️ [MATRIZ DE MODALES DIFF] Discrepancias en modales:');
  console.table(modalDiffs);
  fs.writeFileSync(path.join(DIFF_DIR, 'computed-modals-diff.json'), JSON.stringify(modalDiffs, null, 2));
} else if (fs.existsSync(modals1Path) && fs.existsSync(modals2Path)) {
  console.log(`\n✅ [MODALES] Computed styles de modales 100% idénticos (0 diferencias).`);
}

// Comparación estricta de computed-dropdowns.json (Dropdowns)
const dropdowns1Path = path.join(BASELINE_DIR, 'computed-dropdowns.json');
const dropdowns2Path = path.join(TARGET_DIR, 'computed-dropdowns.json');
const dropdownDiffs = [];
let drop1 = null;
let drop2 = null;

if (fs.existsSync(dropdowns1Path) && fs.existsSync(dropdowns2Path)) {
  drop1 = JSON.parse(fs.readFileSync(dropdowns1Path, 'utf-8'));
  drop2 = JSON.parse(fs.readFileSync(dropdowns2Path, 'utf-8'));

  for (const theme of Object.keys(drop1)) {
    if (!drop2[theme]) {
      dropdownDiffs.push({ tema: theme, parte: '*', propiedad: 'THEME_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
      continue;
    }
    for (const part of Object.keys(drop1[theme])) {
      if (!drop2[theme][part]) {
        dropdownDiffs.push({ tema: theme, parte: part, propiedad: 'PART_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
        continue;
      }
      const props1 = drop1[theme][part];
      const props2 = drop2[theme][part];
      for (const prop of ['backgroundColor', 'borderColor', 'borderTopColor', 'borderTopWidth', 'borderTopStyle', 'color']) {
        if (props1[prop] !== undefined && props2[prop] !== undefined && props1[prop] !== props2[prop]) {
          dropdownDiffs.push({
            tema: theme,
            parte: part,
            propiedad: prop,
            base: props1[prop],
            actual: props2[prop]
          });
        }
      }
    }
  }

  for (const theme of Object.keys(drop2)) {
    if (!drop1[theme]) continue;
    for (const part of Object.keys(drop2[theme])) {
      if (!drop1[theme][part]) {
        dropdownDiffs.push({ tema: theme, parte: part, propiedad: 'PART_EXTRA', base: 'AUSENTE', actual: 'PRESENTE' });
      }
    }
  }
} else if (fs.existsSync(dropdowns2Path) && !fs.existsSync(dropdowns1Path)) {
  console.warn('⚠️ [DROPDOWNS] computed-dropdowns.json presente en actual pero ausente en base.');
}

if (dropdownDiffs.length > 0) {
  console.log('\n⚠️ [MATRIZ DE DROPDOWNS DIFF] Discrepancias en dropdowns:');
  console.table(dropdownDiffs);
  fs.writeFileSync(path.join(DIFF_DIR, 'computed-dropdowns-diff.json'), JSON.stringify(dropdownDiffs, null, 2));
} else if (fs.existsSync(dropdowns1Path) && fs.existsSync(dropdowns2Path)) {
  console.log(`\n✅ [DROPDOWNS] Computed styles de dropdowns 100% idénticos (0 diferencias).`);
}

// Comparación estricta de computed-forms.json (Formularios e Inputs)
const forms1Path = path.join(BASELINE_DIR, 'computed-forms.json');
const forms2Path = path.join(TARGET_DIR, 'computed-forms.json');
const formsDiffs = [];
let form1 = null;
let form2 = null;

if (fs.existsSync(forms1Path) && fs.existsSync(forms2Path)) {
  form1 = JSON.parse(fs.readFileSync(forms1Path, 'utf-8'));
  form2 = JSON.parse(fs.readFileSync(forms2Path, 'utf-8'));

  for (const theme of Object.keys(form1)) {
    if (!form2[theme]) {
      formsDiffs.push({ tema: theme, parte: '*', propiedad: 'THEME_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
      continue;
    }
    for (const part of Object.keys(form1[theme])) {
      if (!form2[theme][part]) {
        formsDiffs.push({ tema: theme, parte: part, propiedad: 'PART_MISSING', base: 'PRESENTE', actual: 'AUSENTE' });
        continue;
      }
      const props1 = form1[theme][part];
      const props2 = form2[theme][part];
      for (const prop of ['backgroundColor', 'borderColor', 'color', 'opacity']) {
        if (props1[prop] !== undefined && props2[prop] !== undefined && props1[prop] !== props2[prop]) {
          formsDiffs.push({
            tema: theme,
            parte: part,
            propiedad: prop,
            base: props1[prop],
            actual: props2[prop]
          });
        }
      }
    }
  }

  for (const theme of Object.keys(form2)) {
    if (!form1[theme]) continue;
    for (const part of Object.keys(form2[theme])) {
      if (!form1[theme][part]) {
        formsDiffs.push({ tema: theme, parte: part, propiedad: 'PART_EXTRA', base: 'AUSENTE', actual: 'PRESENTE' });
      }
    }
  }
} else if (fs.existsSync(forms2Path) && !fs.existsSync(forms1Path)) {
  console.warn('⚠️ [FORMULARIOS] computed-forms.json presente en actual pero ausente en base.');
}

if (formsDiffs.length > 0) {
  console.log('\n⚠️ [MATRIZ DE FORMULARIOS DIFF] Discrepancias en formularios:');
  console.table(formsDiffs);
  fs.writeFileSync(path.join(DIFF_DIR, 'computed-forms-diff.json'), JSON.stringify(formsDiffs, null, 2));
} else if (fs.existsSync(forms1Path) && fs.existsSync(forms2Path)) {
  console.log(`\n✅ [FORMULARIOS] Computed styles de formularios 100% idénticos (0 diferencias).`);
}

const summaryPath = path.join(DIFF_DIR, 'summary.json');
fs.writeFileSync(summaryPath, JSON.stringify({ results, styleDiffs, buttonDiffs, badgeDiffs, alertDiffs, modalDiffs, dropdownDiffs, formsDiffs }, null, 2));

console.log(`\n📊 Resumen de Comparación:`);
console.log(`- Total de capturas evaluadas: ${files.length}`);
console.log(`- Píxeles totales distintos: ${totalDiffPixels}`);
console.log(`- Reporte detallado guardado en: ${summaryPath}`);

if (totalDiffPixels === 0 && !hasCriticalErrors && styleDiffs.length === 0 && buttonDiffs.length === 0 && badgeDiffs.length === 0 && alertDiffs.length === 0 && modalDiffs.length === 0 && dropdownDiffs.length === 0 && formsDiffs.length === 0) {
  console.log('\n🎉 VALIDACIÓN EXITOSA: Determinismo / Identidad total (0 píxeles de diferencia).');
  process.exit(0);
} else {
  console.log(`\n❌ SE DETECTARON DISCREPANCIAS. Revisar imágenes marcadas en ${DIFF_DIR}`);
  process.exit(1);
}
