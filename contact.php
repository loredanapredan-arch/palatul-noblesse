<?php
$title      = 'Contact — Palatul Noblesse';
$meta_desc  = 'Contactați Palatul Noblesse pentru o ofertă personalizată. Telefon, email, adresă și formular cerere ofertă.';
$canonical  = '/contact.php';
$nav_active = 'contact';
require_once 'includes/header.php';
?>
<section class="page-hero">
  <img src="/assets/gradina.jpg" alt="">
  <div class="overlay"></div>
  <div class="container-x content fade-up">
    <div class="eyebrow eyebrow-gold">Contact</div>
    <h1>Hai să vorbim despre evenimentul tău</h1>
    <p class="sub">Completează formularul de mai jos și un event manager vă va contacta în cel mai scurt timp pentru a discuta detaliile.</p>
  </div>
</section>

<section class="py container-x">
  <div class="contact-grid">
    <div class="contact-info reveal">
      <div>
        <span class="eyebrow">Informații</span>
        <h2 style="margin-top:.75rem;font-family:var(--font-display);font-size:2rem">Suntem aici pentru voi</h2>
      </div>
      <div class="row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><div><div class="lbl">Adresă</div><div class="val">Strada Sfinților nr. 7,<br>București, sector 2</div></div></div>
      <div class="row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.86 19.86 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.86 19.86 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg><div><div class="lbl">Telefon</div><div class="val"><a href="tel:+40753667778">+40 753 667 778</a></div></div></div>
      <div class="row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><div><div class="lbl">Email</div><div class="val"><a href="mailto:contact@palatulnoblesse.ro">contact@palatulnoblesse.ro</a></div></div></div>
      <div class="map-wrap"><iframe class="map" src="https://www.google.com/maps?q=Strada%20Sfin%C8%9Bilor%207%2C%20Bucure%C8%99ti&output=embed" loading="lazy" title="Harta Palatul Noblesse"></iframe></div>
    </div>
    <form id="contact-form" class="form-card reveal">
      <div class="form-grid">
        <div class="field"><label>Nume complet *</label><input name="name" required maxlength="255"></div>
        <div class="field"><label>Email *</label><input name="email" type="email" required maxlength="255"></div>
        <div class="field"><label>Telefon *</label><input name="phone" type="tel" required maxlength="255"></div>
        <div class="field"><label>Tip eveniment</label>
          <select name="type" required>
            <option value="">Selectează...</option>
            <option>Nuntă</option><option>Botez</option><option>Corporate</option>
            <option>Petrecere privată</option><option>Petrecere tineri / majorat</option>
            <option>Shooting foto/video</option><option>Altele</option>
          </select>
        </div>
        <div class="field"><label>Data dorită</label><input name="date" type="date"></div>
        <div class="field"><label>Număr invitați</label><input name="guests" type="number"></div>
        <div class="field full"><label>Detalii despre eveniment</label><textarea name="message" rows="5" maxlength="2000"></textarea></div>
      </div>
      <button type="submit" class="btn-primary" style="margin-top:1.5rem">Trimite cererea</button>
      <div id="form-success" style="display:none;margin-top:1rem;color:var(--burgundy);font-size:.875rem">✓ Mulțumim! Vă vom contacta în cel mai scurt timp.</div>
    </form>
  </div>
</section>
<?php require_once 'includes/footer.php'; ?>
