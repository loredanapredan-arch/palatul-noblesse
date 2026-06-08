// Palatul Noblesse — main.js
$(function(){
  // Header scroll state
  var $hdr = $('.site-header');
  function onScroll(){ $hdr.toggleClass('scrolled', $(window).scrollTop() > 40); }
  $(window).on('scroll', onScroll); onScroll();

  // Mobile menu
  $('.menu-toggle').on('click', function(){
    var open = $('.mobile-menu').toggleClass('open').hasClass('open');
    $hdr.toggleClass('menu-open', open);
    $(this).find('.ic-menu').toggle(!open);
    $(this).find('.ic-close').toggle(open);
  });
  $('.mobile-svc-toggle').on('click', function(){
    $(this).next('.mobile-svc').toggleClass('open');
    $(this).find('.chev').toggleClass('rot');
  });

  // Language toggle (RO/EN) — visual only, dictionaries embedded if present
  var stored = localStorage.getItem('lang') || 'ro';
  setLang(stored);
  $('.lang-toggle button').on('click', function(){
    setLang($(this).data('lang'));
  });
  function setLang(l){
    localStorage.setItem('lang', l);
    $('.lang-toggle button').removeClass('active');
    $('.lang-toggle button[data-lang="'+l+'"]').addClass('active');
    if(window.I18N && window.I18N[l]){
      $('[data-i18n]').each(function(){
        var k = $(this).data('i18n');
        if(window.I18N[l][k]) $(this).text(window.I18N[l][k]);
      });
      $('[data-i18n-html]').each(function(){
        var k = $(this).data('i18n-html');
        if(window.I18N[l][k]) $(this).html(window.I18N[l][k]);
      });
    }
    document.documentElement.lang = l;
  }

  // Reveal on scroll (IntersectionObserver)
  if('IntersectionObserver' in window){
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){
        if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, {threshold:0.12, rootMargin:'0px 0px -60px 0px'});
    $('.reveal').each(function(){ io.observe(this); });
  } else { $('.reveal').addClass('in'); }

  // Contact form — salvează în DB + deschide mailto
  $('#contact-form').on('submit', function(e){
    e.preventDefault();
    var $form = $(this);
    var d = {};
    $form.serializeArray().forEach(function(p){ d[p.name]=p.value; });

    $.post('/contact-submit.php', $form.serialize())
      .always(function(){
        var subj = encodeURIComponent('Cerere ofertă — '+(d.type||'Eveniment')+' — '+(d.name||''));
        var body = encodeURIComponent(
          'Nume: '+d.name+'\nEmail: '+d.email+'\nTelefon: '+d.phone+
          '\nTip eveniment: '+d.type+'\nData: '+d.date+'\nInvitați: '+d.guests+
          '\n\nDetalii:\n'+d.message
        );
        window.location.href = 'mailto:contact@palatulnoblesse.ro?subject='+subj+'&body='+body;
        $('#form-success').show();
        $form[0].reset();
      });
  });
});