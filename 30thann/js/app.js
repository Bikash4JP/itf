(function(){
  const GRID = document.getElementById('grid');
  const PAGE_SIZE = 20;
  const styles = getComputedStyle(document.documentElement);
  const STAY = parseInt(styles.getPropertyValue('--page-stay-ms')) || 23000;
  const ENTER = parseInt(styles.getPropertyValue('--enter-ms')) || 800;
  const STAGGER = parseInt(styles.getPropertyValue('--stagger-ms')) || 70;
  const EXIT = parseInt(styles.getPropertyValue('--exit-ms')) || 500;

  let all = [], pages = [], pageIndex = 0, playing = true, cycleTimer=null, refreshTimer=null;

  async function fetchAll(){
    // Proxy on same origin:
    const res = await fetch('/itf/30thann/api/messages.php?limit=1000&offset=0&order=asc',{cache:'no-store'});
    const json = await res.json();
    return (json && json.items) ? json.items : [];
  }
  function chunk(arr, size){ const out=[]; for(let i=0;i<arr.length;i+=size) out.push(arr.slice(i,i+size)); return out; }
  function sanitize(s){ if(!s) return ''; s=String(s).replace(/\s+/g,' ').trim(); if(s.length>220) s=s.slice(0,220)+'…'; return s; }

  function renderPage(items){
    GRID.innerHTML='';
    const list = items.slice(0,PAGE_SIZE);
    while(list.length < PAGE_SIZE) list.push({from:'',to:'',text:''});
    list.forEach((m,idx)=>{
      const card = document.createElement('div'); card.className='card'; card.style.animationDelay=(idx*STAGGER)+'ms';
      const meta = document.createElement('div'); meta.className='meta';
      const from = document.createElement('span'); from.className='chip'; from.textContent = m.from?`From: ${sanitize(m.from)}`:'—';
      const to   = document.createElement('span'); to.className='chip';   to.textContent   = m.to?`To: ${sanitize(m.to)}`:'—';
      meta.appendChild(from); meta.appendChild(to);
      const body = document.createElement('div'); body.className='body'; body.textContent=sanitize(m.text||'');
      card.appendChild(meta); card.appendChild(body); GRID.appendChild(card);
      requestAnimationFrame(()=> card.classList.add('enter'));
    });
  }

  function exitPage(){
    const cards = Array.from(document.querySelectorAll('.card'));
    cards.forEach((c,i)=>{ c.classList.remove('enter'); c.style.animationDelay=(i*15)+'ms'; c.classList.add('exit'); });
  }

  function nextPage(){
    if(!pages.length) return;
    renderPage(pages[pageIndex]);
    clearTimeout(cycleTimer);
    cycleTimer = setTimeout(()=>{
      exitPage();
      setTimeout(()=>{
        pageIndex = (pageIndex+1) % pages.length;
        if(playing) nextPage();
      }, EXIT+50);
    }, STAY);
  }

  async function boot(){
    all = await fetchAll();
    pages = (all.length? chunk(all,PAGE_SIZE) : [[]]);
    pageIndex=0; nextPage();

    // live refresh
    refreshTimer = setInterval(async ()=>{
      try{
        const latest = await fetchAll();
        const changed = JSON.stringify(latest.map(i=>i.id)) !== JSON.stringify(all.map(i=>i.id));
        if(changed){ all = latest; pages = chunk(all,PAGE_SIZE); }
      }catch(e){}
    }, 180000);
  }

  // keyboard helpers
  window.addEventListener('keydown', (e)=>{
    if(e.key==='p' || e.key==='P'){ playing=!playing; if(playing) nextPage(); else clearTimeout(cycleTimer); }
    if(e.key==='n' || e.key==='N'){ clearTimeout(cycleTimer); exitPage(); setTimeout(()=>{ pageIndex=(pageIndex+1)%pages.length; if(playing) nextPage(); }, EXIT+50); }
    if(e.key==='r' || e.key==='R'){ location.reload(); }
  });

  boot();
})();
