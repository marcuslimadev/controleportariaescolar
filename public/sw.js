const CACHE='porta-aberta-shell-v15';
const OFFLINE='./offline.html';
const SHELL=[OFFLINE,'./assets/app.css','./assets/pwa.js','./assets/porta-aberta-app-v2-192.png','./assets/porta-aberta-app-v2-512.png','./assets/login-school-bg.webp','./assets/porta-aberta-logo.jpg','./manifest.webmanifest'];
self.addEventListener('message',event=>{if(event.data&&event.data.type==='SKIP_WAITING')self.skipWaiting()});
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(SHELL)).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key!==CACHE).map(key=>caches.delete(key)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',event=>{
  if(event.request.method!=='GET')return;
  const url=new URL(event.request.url);
  if(url.origin!==location.origin)return;
  if(event.request.mode==='navigate'){event.respondWith(fetch(event.request).catch(()=>caches.match(OFFLINE)));return;}
  if(url.pathname.includes('/uploads/')){event.respondWith(fetch(event.request,{cache:'no-store'}));return;}
  event.respondWith(caches.match(event.request).then(cached=>cached||fetch(event.request).then(response=>{if(response.ok){const copy=response.clone();caches.open(CACHE).then(cache=>cache.put(event.request,copy));}return response})));
});
