<?php
/**
 * head_bootstrap.php
 * À inclure dans le <head> de chaque page via :
 *   <?php include 'head_bootstrap.php'; ?>
 *
 * Intègre :
 *   - Bootstrap 5.3 (CSS + JS via CDN)
 *   - Alpine.js 3 (interactions légères via CDN)
 *   - Variables CSS du projet conservées
 *   - Surcharges Bootstrap pour coller à la charte CY Stage
 */
?>
<!-- ═══ Bootstrap 5.3 CSS ═══ -->
<link
  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
  rel="stylesheet"
  integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
  crossorigin="anonymous"
>

<!-- ═══ Bootstrap Icons (optionnel mais très utile) ═══ -->
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>

<!-- ═══ Alpine.js 3 ═══ -->
<script
  defer
  src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js">
</script>

<!-- ═══ Surcharges Bootstrap — Charte CY Stage ═══
     On surcharge uniquement ce qui entre en conflit avec
     vos CSS existants. Vos classes custom (.carte, .btn-coeur…)
     restent inchangées.
-->
<style>
  /* Palette Bootstrap alignée sur votre charte */
  :root {
    --bs-primary:        #1B4F9B;   /* --bleu du projet       */
    --bs-primary-rgb:    27,79,155;
    --bs-secondary:      #5686D9;   /* --bleu-clair            */
    --bs-body-font-family: 'DM Sans', 'Montserrat', sans-serif;
    --bs-border-radius:  14px;
    --bs-border-radius-sm: 8px;
  }

  /* Bouton primary Bootstrap → couleur du projet */
  .btn-primary {
    background-color: var(--bs-primary);
    border-color:     var(--bs-primary);
  }
  .btn-primary:hover,
  .btn-primary:focus {
    background-color: #14397a;
    border-color:     #14397a;
  }
  .btn-outline-primary {
    color:        var(--bs-primary);
    border-color: var(--bs-primary);
  }
  .btn-outline-primary:hover {
    background-color: var(--bs-primary);
    border-color:     var(--bs-primary);
  }

  /* Liens actifs Bootstrap */
  a { color: var(--bs-primary); }

  /* Badges Bootstrap → harmonie avec vos .badge custom */
  .badge.bg-primary { background-color: var(--bs-primary) !important; }
</style>

<!-- ═══ Bootstrap 5.3 JS Bundle (Popper inclus) ═══ -->
<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmB8MpqoD+3r6amLz7LfNm7dmI5"
  crossorigin="anonymous">
</script>