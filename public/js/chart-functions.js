// ============ SVG Chart Renderer (tanpa library eksternal) ============
const CHART_COLORS = ['#10243E','#C9A24B','#2E7D6B','#8AA1BC','#C0533B','#5C7A99','#DBC48C','#4E9481','#7C93A8','#A9744B'];

function svgEl(tag, attrs){
  const el = document.createElementNS('http://www.w3.org/2000/svg', tag);
  for(const k in attrs) el.setAttribute(k, attrs[k]);
  return el;
}

// Horizontal bar chart
function renderHBarChart(containerId, labels, values, opts={}){
  const container = document.getElementById(containerId);
  const W = container.clientWidth || 480, rowH = opts.rowH || 26, gap = 8;
  const labelW = opts.labelW || 190;
  const chartW = W - labelW - 60;
  const H = labels.length * (rowH + gap) + 20;
  const maxVal = Math.max(...values, 1);
  const color = opts.color || '#10243E';

  const svg = svgEl('svg', {viewBox:`0 0 ${W} ${H}`, width:'100%', height:H, style:'font-family:inherit;'});
  labels.forEach((label, i)=>{
    const y = i*(rowH+gap) + 10;
    const barW = Math.max((values[i]/maxVal) * chartW, 2);
    const t = svgEl('text', {x:labelW-8, y:y+rowH/2+4, 'text-anchor':'end', 'font-size':'11.5', fill:'#1B2733'});
    t.textContent = label;
    svg.appendChild(t);
    const rect = svgEl('rect', {x:labelW, y:y, width:barW, height:rowH, rx:4, fill:color, style: opts.clickable ? 'cursor:pointer' : ''});
    if(opts.onClick) rect.addEventListener('click', ()=>opts.onClick(i));
    svg.appendChild(rect);
    const vt = svgEl('text', {x:labelW+barW+8, y:y+rowH/2+4, 'font-size':'11.5', fill:'#6B7480', 'font-weight':'600'});
    vt.textContent = values[i];
    svg.appendChild(vt);
  });
  container.innerHTML = '';
  container.appendChild(svg);
}

// Donut chart
function renderDonutChart(containerId, labels, values, colors=CHART_COLORS){
  const container = document.getElementById(containerId);
  const size = 220, cx = 110, cy = 110, rOuter = 90, rInner = 55;
  const total = values.reduce((a,b)=>a+b,0);
  let angleStart = -Math.PI/2;

  const svg = svgEl('svg', {viewBox:`0 0 ${size+150} ${size}`, width:'100%', height:size});
  values.forEach((v,i)=>{
    const angle = (v/total) * Math.PI*2;
    const angleEnd = angleStart + angle;
    const x1 = cx + rOuter*Math.cos(angleStart), y1 = cy + rOuter*Math.sin(angleStart);
    const x2 = cx + rOuter*Math.cos(angleEnd), y2 = cy + rOuter*Math.sin(angleEnd);
    const x1i = cx + rInner*Math.cos(angleEnd), y1i = cy + rInner*Math.sin(angleEnd);
    const x2i = cx + rInner*Math.cos(angleStart), y2i = cy + rInner*Math.sin(angleStart);
    const largeArc = angle > Math.PI ? 1 : 0;
    const d = `M ${x1} ${y1} A ${rOuter} ${rOuter} 0 ${largeArc} 1 ${x2} ${y2} L ${x1i} ${y1i} A ${rInner} ${rInner} 0 ${largeArc} 0 ${x2i} ${y2i} Z`;
    svg.appendChild(svgEl('path', {d, fill: colors[i % colors.length]}));
    angleStart = angleEnd;
  });
  // legend
  labels.forEach((label,i)=>{
    const ly = 20 + i*22;
    svg.appendChild(svgEl('rect', {x:size+10, y:ly, width:12, height:12, rx:2, fill:colors[i%colors.length]}));
    const t = svgEl('text', {x:size+28, y:ly+10, 'font-size':'11.5', fill:'#1B2733'});
    t.textContent = `${label} (${values[i]})`;
    svg.appendChild(t);
  });
  container.innerHTML = '';
  container.appendChild(svg);
}

// Line chart (dipakai untuk Lorenz curve, 2 garis)
function renderLineChart(containerId, series, opts={}){
  const container = document.getElementById(containerId);
  const W = container.clientWidth || 480, H = opts.height || 260;
  const padL = 45, padB = 30, padT = 15, padR = 15;
  const chartW = W - padL - padR, chartH = H - padT - padB;
  const svg = svgEl('svg', {viewBox:`0 0 ${W} ${H}`, width:'100%', height:H});

  // axes
  svg.appendChild(svgEl('line', {x1:padL, y1:padT, x2:padL, y2:H-padB, stroke:'#E4E0D6'}));
  svg.appendChild(svgEl('line', {x1:padL, y1:H-padB, x2:W-padR, y2:H-padB, stroke:'#E4E0D6'}));
  [0,25,50,75,100].forEach(v=>{
    const y = H-padB - (v/100)*chartH;
    svg.appendChild(svgEl('text', {x:padL-6, y:y+3, 'text-anchor':'end', 'font-size':'10', fill:'#6B7480'})).textContent=v;
    const x = padL + (v/100)*chartW;
    svg.appendChild(svgEl('text', {x:x, y:H-padB+16, 'text-anchor':'middle', 'font-size':'10', fill:'#6B7480'})).textContent=v;
  });

  series.forEach(s=>{
    const pts = s.data.map((pt)=>{
      const x = padL + (pt.x/100)*chartW;
      const y = H-padB - (pt.y/100)*chartH;
      return `${x},${y}`;
    }).join(' ');
    const attrs = {points:pts, fill:'none', stroke:s.color, 'stroke-width':2};
    if(s.dash) attrs['stroke-dasharray'] = s.dash;
    svg.appendChild(svgEl('polyline', attrs));
    if(s.fill){
      const areaPts = `${padL},${H-padB} ` + pts + ` ${padL+chartW},${H-padB}`;
      svg.appendChild(svgEl('polygon', {points:areaPts, fill:s.color, opacity:0.12}));
    }
  });

  // legend
  series.forEach((s,i)=>{
    const ly = 12 + i*16;
    svg.appendChild(svgEl('line', {x1:W-160, y1:ly, x2:W-140, y2:ly, stroke:s.color, 'stroke-width':2, 'stroke-dasharray': s.dash||''}));
    const t = svgEl('text', {x:W-134, y:ly+4, 'font-size':'10.5', fill:'#1B2733'});
    t.textContent = s.label;
    svg.appendChild(t);
  });

  container.innerHTML = '';
  container.appendChild(svg);
}


// Grouped bar chart (untuk perbandingan 2 kelompok, mis. sebelum/sesudah)
function renderGroupedBarChart(containerId, labels, series, opts={}){
  const container = document.getElementById(containerId);
  const W = container.clientWidth || 480, H = opts.height || 280;
  const padL = 45, padB = 40, padT = 20, padR = 15;
  const chartW = W - padL - padR, chartH = H - padT - padB;
  const groupW = chartW / labels.length;
  const barW = groupW / (series.length + 1);
  const maxVal = Math.max(...series.flatMap(s=>s.data), 1);

  const svg = svgEl('svg', {viewBox:`0 0 ${W} ${H}`, width:'100%', height:H});
  svg.appendChild(svgEl('line', {x1:padL, y1:H-padB, x2:W-padR, y2:H-padB, stroke:'#E4E0D6'}));
  [0,0.25,0.5,0.75,1].forEach(f=>{
    const y = H-padB - f*chartH;
    svg.appendChild(svgEl('text', {x:padL-6, y:y+3, 'text-anchor':'end', 'font-size':'10', fill:'#6B7480'})).textContent=(f*maxVal).toFixed(1);
    svg.appendChild(svgEl('line', {x1:padL, y1:y, x2:W-padR, y2:y, stroke:'#F0EEE7'}));
  });

  labels.forEach((label,i)=>{
    const gx = padL + i*groupW;
    series.forEach((s,si)=>{
      const val = s.data[i];
      const barH = (val/maxVal) * chartH;
      const x = gx + (si+0.5)*barW;
      svg.appendChild(svgEl('rect', {x:x, y:H-padB-barH, width:barW*0.8, height:barH, rx:3, fill:s.color}));
      const vt = svgEl('text', {x:x+barW*0.4, y:H-padB-barH-5, 'text-anchor':'middle', 'font-size':'10', fill:'#1B2733', 'font-weight':'600'});
      vt.textContent = val.toFixed(2);
      svg.appendChild(vt);
    });
    const lt = svgEl('text', {x:gx+groupW/2, y:H-padB+16, 'text-anchor':'middle', 'font-size':'11', fill:'#1B2733'});
    lt.textContent = label;
    svg.appendChild(lt);
  });

  // legend
  series.forEach((s,i)=>{
    const lx = padL + i*130;
    svg.appendChild(svgEl('rect', {x:lx, y:2, width:11, height:11, rx:2, fill:s.color}));
    const t = svgEl('text', {x:lx+16, y:11, 'font-size':'10.5', fill:'#1B2733'});
    t.textContent = s.label;
    svg.appendChild(t);
  });

  container.innerHTML = '';
  container.appendChild(svg);
}

// Confusion matrix sederhana (kotak 2x2 berwarna)
function renderConfusionMatrix(containerId, matrix, labels){
  // matrix = [[TP, FN],[FP, TN]] ; labels = ['Aktif','Kurang Aktif']
  const container = document.getElementById(containerId);
  const html = `
    <div class="cm-grid">
      <div></div>
      <div class="cm-label">Prediksi: ${labels[0]}</div>
      <div class="cm-label">Prediksi: ${labels[1]}</div>
      <div class="cm-label">Aktual:<br>${labels[0]}</div>
      <div class="cm-cell cm-correct"><div class="cm-num">${matrix[0][0]}</div><div class="cm-sub">Benar</div></div>
      <div class="cm-cell cm-wrong"><div class="cm-num">${matrix[0][1]}</div><div class="cm-sub">Salah</div></div>
      <div class="cm-label">Aktual:<br>${labels[1]}</div>
      <div class="cm-cell cm-wrong"><div class="cm-num">${matrix[1][0]}</div><div class="cm-sub">Salah</div></div>
      <div class="cm-cell cm-correct"><div class="cm-num">${matrix[1][1]}</div><div class="cm-sub">Benar</div></div>
    </div>`;
  container.innerHTML = html;
}

// ---------- Model Performance ----------
// Data metrik (hasil dari notebook: Decision Tree max_depth=4, balancing via random oversampling)
const METRICS_SEBELUM = { accuracy:0.850, precision:0.333, recall:0.034, f1:0.062 };
const METRICS_SESUDAH = { accuracy:0.715, precision:0.259, recall:0.517, f1:0.345 };

renderGroupedBarChart('chartClassBalance',
  ['Kurang Aktif', 'Aktif'],
  [{ label:'Jumlah ASN', data:[856, 144], color:'#C0533B' }],
  { height: 220 }
);

renderGroupedBarChart('chartMetricCompare',
  ['Accuracy', 'Precision', 'Recall', 'F1-Score'],
  [
    { label:'Sebelum Balancing', data:[METRICS_SEBELUM.accuracy, METRICS_SEBELUM.precision, METRICS_SEBELUM.recall, METRICS_SEBELUM.f1], color:'#C0533B' },
    { label:'Sesudah Balancing', data:[METRICS_SESUDAH.accuracy, METRICS_SESUDAH.precision, METRICS_SESUDAH.recall, METRICS_SESUDAH.f1], color:'#2E7D6B' }
  ]
);

document.getElementById('metricExplain').innerHTML = `
  <b>Kenapa Accuracy turun tapi kok dianggap "lebih baik"?</b><br>
  Accuracy tinggi (85%) sebelum balancing itu menipu — model cuma jago nebak "Kurang Aktif" terus (kelas mayoritas), sampai nyaris tidak bisa mengenali ASN yang "Aktif" (Recall cuma 3,4%).
  Setelah balancing, Recall naik jadi 51,7% — artinya model jauh lebih peka mendeteksi ASN yang benar-benar aktif, meski Accuracy keseluruhan turun.
  Untuk tujuan BKPP (mencari ASN yang perlu diprioritaskan), <b>kemampuan mendeteksi yang benar lebih penting daripada angka accuracy semata</b>.
`;

renderConfusionMatrix('confMatrixBefore', [[1,28],[2,169]], ['Aktif','Kurang Aktif']);
renderConfusionMatrix('confMatrixAfter', [[15,14],[43,128]], ['Aktif','Kurang Aktif']);

renderHBarChart('chartFeatureModel',
  FEAT_IMPORTANCE.map(f=>f.fitur),
  FEAT_IMPORTANCE.map(f=>f.chi2),
  { color:'#C9A24B', labelW: 210 }
);

document.getElementById('ruleCards').innerHTML = `
  <div class="rule-card">
    <div class="rule-text">ASN dengan <b>jabatan struktural (eselon)</b> dan <b>pendidikan S1 ke atas</b> cenderung diprediksi <b>Aktif</b> bangkom.</div>
  </div>
  <div class="rule-card">
    <div class="rule-text">ASN dengan <b>golongan IV/a ke atas</b> dan <b>sisa masa kerja 7–10 tahun</b> juga cenderung <b>Aktif</b> — kemungkinan sedang dipersiapkan untuk jenjang karier lebih tinggi.</div>
  </div>
  <div class="rule-card">
    <div class="rule-text">ASN <b>non-struktural</b> dengan <b>pendidikan di bawah S1</b> hampir selalu diprediksi <b>Kurang Aktif</b> — kelompok ini paling butuh perhatian BKPP.</div>
  </div>
  <div class="rule-card">
    <div class="rule-text">Secara umum, <b>status jabatan struktural (eselon)</b> adalah faktor paling menentukan yang membedakan ASN aktif vs kurang aktif — jauh lebih berpengaruh daripada jenis kelamin maupun pendidikan.</div>
  </div>
`;

