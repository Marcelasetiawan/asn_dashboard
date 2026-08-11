const API = "/api";

// ---------- Hamburger & Navigasi ----------
function openSidebar(){
  document.getElementById('sidebarEl').classList.add('open');
  document.getElementById('sidebarBackdrop').classList.add('show');
  document.body.classList.add('sidebar-open');
}
function closeSidebar(){
  document.getElementById('sidebarEl').classList.remove('open');
  document.getElementById('sidebarBackdrop').classList.remove('show');
  document.body.classList.remove('sidebar-open');
}
document.getElementById('hamburgerBtn').addEventListener('click', ()=>{
  document.getElementById('sidebarEl').classList.contains('open') ? closeSidebar() : openSidebar();
});
document.getElementById('sidebarBackdrop').addEventListener('click', closeSidebar);
if(window.innerWidth > 900){ openSidebar(); }

const pageLoaders = {
  'ringkasan': loadRingkasan,
  'diklat-unit': loadDiklatUnit,
  'analisis-lanjutan': loadAnalisisLanjutan,
  'prediksi': () => {},
};
const loadedPages = new Set();

document.querySelectorAll('.nav-item').forEach(el=>{
  el.addEventListener('click', ()=>{
    document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
    document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
    el.classList.add('active');
    const pageId = el.dataset.page;
    document.getElementById('page-'+pageId).classList.add('active');
    if(!loadedPages.has(pageId)){
      loadedPages.add(pageId);
      pageLoaders[pageId] && pageLoaders[pageId]();
    }
    if(window.innerWidth <= 900){ closeSidebar(); }
  });
});

function escapeHtml(str){
  return String(str==null?'':str).replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
function closeModal(){ document.getElementById('overlay').classList.remove('show'); }
document.getElementById('overlay').addEventListener('click', e=>{ if(e.target.id==='overlay') closeModal(); });
document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModal(); });

// ══════════════════════════════════════════════════════════════════════
// HALAMAN 1: RINGKASAN (dengan filter tahun + cross-filter jenis<->top diklat)
// ══════════════════════════════════════════════════════════════════════
let ringkasanTahunLoaded = false;

async function loadRingkasan(){
  const tahun = document.getElementById('filterTahun').value;
  const res = await fetch(`${API}/ringkasan?tahun=${encodeURIComponent(tahun)}`);
  const d = await res.json();

  if(!ringkasanTahunLoaded){
    const sel = document.getElementById('filterTahun');
    d.tahun_tersedia.forEach(t=>{
      const opt = document.createElement('option'); opt.value = t; opt.textContent = t; sel.appendChild(opt);
    });
    sel.addEventListener('change', loadRingkasan);
    ringkasanTahunLoaded = true;
  }

  document.getElementById('stat-grid').innerHTML = `
    <div class="stat-card"><div class="num">${d.total_asn.toLocaleString('id-ID')}</div><div class="label">Total ASN</div></div>
    <div class="stat-card"><div class="num">${d.total_riwayat.toLocaleString('id-ID')}</div><div class="label">Total Riwayat Diklat${tahun? ' (tahun '+tahun+')':''}</div></div>
    <div class="stat-card"><div class="num">${d.pct_memenuhi_20jp}%</div><div class="label">ASN Memenuhi Standar 20 JP/Tahun (${d.asn_memenuhi_20jp.toLocaleString('id-ID')} orang)</div></div>
    <div class="stat-card"><div class="num">${d.total_jp.toLocaleString('id-ID')}</div><div class="label">Total Jam Pelajaran (JP)</div></div>
  `;

  window._top15Data = d.top15_diklat;
  window._jenisData = d.jenis_distribusi;
  renderRingkasanCharts(window._top15Data, window._jenisData);

  const jpTahun = d.rata_jp_per_tahun;
  renderLineChart('chartJpTahun', [
    { label:'Total JP', data: jpTahun.map(x=>({x:x.tahun, y:x.total_jp})), color:'#10243E' }
  ]);
}

function renderRingkasanCharts(top15, jenisData, jenisAktif){
  const filtered = jenisAktif ? top15.filter(x=>x.jenis===jenisAktif) : top15;
  renderHBarChart('chartTop',
    filtered.map(x=>x.nama.length>38? x.nama.slice(0,38)+'…': x.nama),
    filtered.map(x=>x.jumlah),
    { color:'#10243E', clickable:true, onClick:(i)=>{
        const item = filtered[i];
        document.querySelector('.nav-item[data-page="diklat-unit"]').click();
        setTimeout(()=>{ document.getElementById('searchDiklat').value = item.nama; applyDiklatUnitFilter(); }, 60);
      }}
  );

  renderDonutChart('chartJenis', jenisData.map(x=>x.jenis), jenisData.map(x=>x.jumlah));
  // cross-filter: klik jenis (donut tidak ada onClick bawaan, jadi kita pakai klik pada legend teks via delegasi sederhana)
  const jenisContainer = document.getElementById('chartJenis');
  jenisContainer.style.cursor = 'pointer';
  jenisContainer.onclick = (e) => {
    // cari index legend yang diklik berdasarkan posisi Y kasar (donut chart legend disusun vertikal)
    const svg = jenisContainer.querySelector('svg');
    if(!svg) return;
    const rect = svg.getBoundingClientRect();
    const relY = e.clientY - rect.top;
    const idx = Math.floor((relY - 14) / 22);
    if(idx >= 0 && idx < jenisData.length){
      const jenis = jenisData[idx].jenis;
      const isActive = jenisContainer.dataset.active === jenis;
      jenisContainer.dataset.active = isActive ? '' : jenis;
      renderRingkasanCharts(top15, jenisData, isActive ? null : jenis);
      document.getElementById('chartTop').closest('.card').querySelector('.sub').textContent =
        isActive ? 'Klik batang untuk memfilter halaman "Diklat & Unit Kerja".'
                  : `Difilter jenis: ${jenis} — klik lagi bagian yang sama di chart sebelah untuk reset.`;
    }
  };
}

// ══════════════════════════════════════════════════════════════════════
// HALAMAN 2: DIKLAT & UNIT KERJA (GABUNGAN, saling filter)
// ══════════════════════════════════════════════════════════════════════
let filterOptionsLoaded = false;

async function loadDiklatUnit(){
  if(!filterOptionsLoaded){
    document.getElementById('searchDiklat').addEventListener('input', debounce(applyDiklatUnitFilter, 300));
    document.getElementById('filterJenis').addEventListener('change', applyDiklatUnitFilter);
    document.getElementById('filterUnitDiklat').addEventListener('change', applyDiklatUnitFilter);
    filterOptionsLoaded = true;
  }
  await applyDiklatUnitFilter(true);
}

function debounce(fn, ms){
  let t; return (...args)=>{ clearTimeout(t); t = setTimeout(()=>fn(...args), ms); };
}

async function applyDiklatUnitFilter(isFirstLoad){
  const q = document.getElementById('searchDiklat').value;
  const jenis = document.getElementById('filterJenis').value;
  const unit = document.getElementById('filterUnitDiklat').value;

  const params = new URLSearchParams({q, jenis, unit});
  const res = await fetch(`${API}/diklat-unit?${params.toString()}`);
  const d = await res.json();

  document.getElementById('hasilFilterInfo').textContent = `${d.total_hasil} baris riwayat cocok dengan filter saat ini.`;

  document.getElementById('tblDiklat').innerHTML = d.diklat_list.map(x=>`
    <tr class="clickable" onclick='openDiklatModal(${JSON.stringify(x.nama_diklat)})'>
      <td>${escapeHtml(x.nama_diklat)}</td><td>${escapeHtml(x.jenis||'-')}</td><td>${x.jumlah}</td></tr>`).join('')
    || `<tr><td colspan="3" class="empty-state">Tidak ada diklat yang cocok.</td></tr>`;

  document.getElementById('tblUnit').innerHTML = d.unit_rekap.map(x=>`
    <tr><td>${escapeHtml(x.unit_kerja)}</td><td>${x.jumlah_riwayat}</td><td>${x.jumlah_pegawai_terlibat}</td></tr>`).join('')
    || `<tr><td colspan="3" class="empty-state">Tidak ada data.</td></tr>`;

  // isi dropdown filter (sekali saja saat load pertama, dari data unfiltered)
  if(isFirstLoad){
    const jenisSel = document.getElementById('filterJenis');
    const unitSel = document.getElementById('filterUnitDiklat');
    const jenisUnik = [...new Set(d.diklat_list.map(x=>x.jenis).filter(Boolean))];
    jenisUnik.forEach(j=>{ const opt=document.createElement('option'); opt.value=j; opt.textContent=j; jenisSel.appendChild(opt); });
    const unitUnik = d.unit_rekap.map(x=>x.unit_kerja).sort();
    unitUnik.forEach(u=>{ const opt=document.createElement('option'); opt.value=u; opt.textContent=u; unitSel.appendChild(opt); });
  }
}

async function openDiklatModal(nama){
  const res = await fetch(`${API}/diklat-peserta?nama=${encodeURIComponent(nama)}`);
  const list = await res.json();
  const rowsHtml = list.map(p=>`
    <tr><td class="mono" style="font-size:12px;">${p.nip}</td><td>${escapeHtml(p.nama)}</td>
    <td>${escapeHtml(p.satuan_kerja||'')}</td><td>${escapeHtml(p.pelaksanaan||'-')}</td><td>${p.jp ?? '-'}</td></tr>`).join('');
  document.getElementById('modalContent').innerHTML = `
    <div class="modal-head"><div><h3>${escapeHtml(nama)}</h3><div class="modal-sub">${list.length} peserta</div></div>
      <button class="modal-close" onclick="closeModal()">✕</button></div>
    <div class="scroll-y"><table><thead><tr><th>NIP</th><th>Nama</th><th>Unit Kerja</th><th>Pelaksanaan</th><th>JP</th></tr></thead>
    <tbody>${rowsHtml}</tbody></table></div>`;
  document.getElementById('overlay').classList.add('show');
}

// ══════════════════════════════════════════════════════════════════════
// HALAMAN 3: ANALISIS LANJUTAN
// ══════════════════════════════════════════════════════════════════════
async function loadAnalisisLanjutan(){
  const res = await fetch(`${API}/analisis-lanjutan`);
  const d = await res.json();

  renderHBarChart('chartJpTerendah',
    d.rata_jp_terendah.map(u=>u.unit_kerja.length>28? u.unit_kerja.slice(0,28)+'…':u.unit_kerja),
    d.rata_jp_terendah.map(u=>u.rata_jp),
    { color:'#C0533B', labelW: 170 }
  );
  renderHBarChart('chartJpTertinggi',
    d.rata_jp_tertinggi.map(u=>u.unit_kerja.length>28? u.unit_kerja.slice(0,28)+'…':u.unit_kerja),
    d.rata_jp_tertinggi.map(u=>u.rata_jp),
    { color:'#2E7D6B', labelW: 170 }
  );

  renderDonutChart('chartRelevansi', d.relevansi_count.map(x=>x.kategori), d.relevansi_count.map(x=>x.jumlah));
  document.getElementById('relevansiSub').textContent =
    `${d.pct_generik}% dari seluruh riwayat diklat adalah diklat generik (soft-skill/budaya kerja) yang berlaku untuk ASN apapun profesinya — bukan diklat teknis spesifik sesuai bidang tugas.`;

  document.getElementById('tblCrossmatch').innerHTML = d.crossmatch_contoh.map(c=>{
    const unitList = Object.entries(c.unit_terbanyak).map(([u,n])=>`${escapeHtml(u)} (${n})`).join(', ');
    return `<tr><td>${escapeHtml(c.nama_diklat)}</td><td>${c.jumlah_peserta}</td><td style="font-size:12px;">${unitList}</td></tr>`;
  }).join('') || `<tr><td colspan="3" class="empty-state">Tidak ada data.</td></tr>`;
}

// ══════════════════════════════════════════════════════════════════════
// HALAMAN 4: PREDIKSI
// ══════════════════════════════════════════════════════════════════════
async function jalankanPrediksi(){
  const body = {
    gender: Number(document.getElementById('predGender').value),
    golongan: Number(document.getElementById('predGolongan').value),
    pendidikan: Number(document.getElementById('predPendidikan').value),
    eselon: Number(document.getElementById('predEselon').value),
    masa_kerja: Number(document.getElementById('predMasaKerja').value),
    sisa_masa_kerja: Number(document.getElementById('predSisaMasaKerja').value),
  };
  const res = await fetch(`${API}/prediksi`, {
    method: 'POST',
    headers: {'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify(body)
  });
  const hasil = await res.json();
  const cls = hasil.label === 'Memenuhi Standar' ? 'aktif' : 'kurang';
  const k = hasil.konteks;

  document.getElementById('predictOutput').innerHTML = `
    <div class="predict-result ${cls}">
      <div class="big-label">Prediksi: ${hasil.label}</div>
      <div class="proba">Tingkat keyakinan model: ${Math.round(hasil.proba*100)}%</div>
    </div>
    <div class="predict-section">
      <h4>Konteks dari Data Riil</h4>
      <p class="rekomendasi-text">Dari <b>${k.jumlah_mirip} ASN</b> di database dengan profil serupa (${escapeHtml(k.basis)}),
      <b>${k.pct_memenuhi_20jp}%</b> di antaranya benar-benar memenuhi standar 20 JP/tahun saat ini.</p>
    </div>
  `;
}

// ---------- Load halaman pertama ----------
loadRingkasan();
loadedPages.add('ringkasan');
