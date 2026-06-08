# Palatul Noblesse — versiune statică

Site complet HTML / Bootstrap 5 / jQuery 3, generat din proiectul React.

## Cum se folosește
1. Dezarhivați folderul oriunde pe disc.
2. Deschideți `index.html` direct în browser **sau** publicați tot folderul pe orice hosting static
   (Netlify, Vercel, Cloudflare Pages, GitHub Pages, FTP clasic etc.).

## Structură
- `index.html`, `despre.html`, `portofoliu.html`, `servicii.html`, `saloane.html`, `galerie.html`, `contact.html` — paginile principale
- `portofoliu/*.html` — pagini detaliu pentru fiecare tip de eveniment (nuntă, botez, corporate, etc.)
- `servicii/*.html` — pagini detaliu pentru fiecare categorie de servicii
- `assets/` — imagini
- `css/styles.css` — designul complet (paleta burgundy/gold, fonturi Cormorant + Inter)
- `js/main.js` — interacțiuni jQuery (meniu, scroll header, dropdown, animații reveal cu IntersectionObserver, formular contact)

## Dependențe externe (CDN)
- Bootstrap 5.3.3 (CSS + JS bundle)
- jQuery 3.7.1
- Google Fonts: Cormorant Garamond + Inter

Nu necesită build, npm sau server. Doar HTML/CSS/JS pure.

## Formular contact
Trimite mesajul prin `mailto:` — deschide clientul de email al utilizatorului
cu subiectul și conținutul precompletate. Pentru trimitere automată pe server,
înlocuiți handler-ul din `js/main.js` cu un POST către endpoint-ul vostru.
