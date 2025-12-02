// js/app.js — friends + avatar preview (localStorage)
(() => {
  const friendInput = document.getElementById('friendInput');
  const addFriendBtn = document.getElementById('addFriendBtn');
  const friendsList = document.getElementById('friendsList');
  const avatarPreview = document.getElementById('avatarPreview');
  const mainAvatar = document.getElementById('mainAvatar');
  const miniAvatar = document.getElementById('miniAvatar');
  const sideAvatar = document.getElementById('sideAvatar');
  const userName = document.getElementById('userName');
  const greetName = document.getElementById('greetName');
  const qFriends = document.getElementById('qFriends');

  const sampleFriends = [
    {name:'Melisa', avatar:'https://i.pravatar.cc/80?img=11', status:'online'},
    {name:'Baran', avatar:'https://i.pravatar.cc/80?img=12', status:'offline'},
    {name:'Defne', avatar:'https://i.pravatar.cc/80?img=13', status:'online'}
  ];

  // friend list
  function loadFriends(){
    let saved = JSON.parse(localStorage.getItem('ch_friends') || 'null');
    const list = saved || sampleFriends;
    friendsList.innerHTML = '';
    list.forEach((f, idx) => {
      const li = document.createElement('li');
      li.className = 'friend-item';
      li.innerHTML = `<div class="friend-avatar"><img src="${escapeHtml(f.avatar)}" style="width:100%;height:100%;object-fit:cover;border-radius:50%"></div>
        <div class="friend-meta"><div class="friend-name">${escapeHtml(f.name)}</div><div class="friend-status">${escapeHtml(f.status)}</div></div>
        <div class="friend-actions"><button class="btn small" data-idx="${idx}">Sil</button></div>`;
      friendsList.appendChild(li);
    });
    qFriends && (qFriends.textContent = list.length);
  }

  addFriendBtn.addEventListener('click', ()=>{
    const name = friendInput.value.trim();
    if(!name) return;
    const friends = JSON.parse(localStorage.getItem('ch_friends') || '[]');
    friends.push({name, avatar:`https://i.pravatar.cc/80?u=${encodeURIComponent(name)}`, status: Math.random()>0.5?'online':'offline'});
    localStorage.setItem('ch_friends', JSON.stringify(friends));
    friendInput.value = '';
    loadFriends();
  });

  friendsList.addEventListener('click', (e)=>{
    const btn = e.target.closest('button');
    if(!btn) return;
    const idx = +btn.dataset.idx;
    const friends = JSON.parse(localStorage.getItem('ch_friends') || '[]');
    if(friends.length){
      friends.splice(idx,1);
      localStorage.setItem('ch_friends', JSON.stringify(friends));
    } else {
      // if sample in use, remove from sample array
      sampleFriends.splice(idx,1);
    }
    loadFriends();
  });

  function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

  // avatar preview render from localStorage
  function renderAvatar(){
    const data = JSON.parse(localStorage.getItem('ch_avatar') || '{}');
    const skin = data.skin || '#FFEFD5';
    const hair = data.hair || '#F7E5A9';
    const eye = data.eye || '#2B2B2B';
    const top = data.top || '#FF8A00';

    const svg = `<svg width="180" height="180" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
      <ellipse cx="100" cy="150" rx="46" ry="28" fill="${skin}" />
      <circle cx="100" cy="80" r="36" fill="${skin}" />
      <path d="M60 70 q40 -40 80 0 q-20 40 -80 0" fill="${hair}"></path>
      <ellipse cx="85" cy="82" rx="6" ry="8" fill="${eye}"></ellipse>
      <ellipse cx="115" cy="82" rx="6" ry="8" fill="${eye}"></ellipse>
      <rect x="62" y="120" width="76" height="38" rx="8" fill="${top}"></rect>
    </svg>`;

    avatarPreview.innerHTML = svg;
    mainAvatar.innerHTML = svg;
    sideAvatar.innerHTML = svg;
    miniAvatar.innerHTML = svg;
  }

  // randomize quick (demo)
  document.getElementById('randomize').addEventListener('click', ()=>{
    const hairOptions = ['#F7E5A9','#FFD98A','#E6C07A','#FCE39A'];
    const skinOptions = ['#FFEFD5','#FFE6CC','#FFE8D6'];
    const eyeOptions = ['#2B2B2B','#1A1A1A','#5A3E2B'];
    const topOptions = ['#FF8A00','#FFB35A','#D97A00'];
    const v = {
      hair: hairOptions[Math.floor(Math.random()*hairOptions.length)],
      skin: skinOptions[Math.floor(Math.random()*skinOptions.length)],
      eye: eyeOptions[Math.floor(Math.random()*eyeOptions.length)],
      top: topOptions[Math.floor(Math.random()*topOptions.length)],
      style:'style1', expr:'f2'
    };
    localStorage.setItem('ch_avatar', JSON.stringify(v));
    renderAvatar();
  });

  // clear avatar
  document.getElementById('clearAvatar').addEventListener('click', ()=>{
    localStorage.removeItem('ch_avatar');
    renderAvatar();
  });

  // set bg mode attribute if --bg-mode in root (legacy support)
  (function setBg(){
    try {
      const root = getComputedStyle(document.documentElement).getPropertyValue('--bg-mode')||'BG2';
      document.body.setAttribute('data-bg', root.replace(/["']/g,'').trim());
    } catch(e){}
  })();

  // init
  loadFriends();
  renderAvatar();
})();
