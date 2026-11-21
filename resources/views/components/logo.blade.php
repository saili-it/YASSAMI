{{--
    Logo officiel YASSAMI — point d'entrée UNIQUE du logo sur tout le site.
    Fichiers générés depuis resources/brand/yassami-logo-original.png (blanc sur noir) :
      public/images/brand/logo.png        → logo noir, pour fonds clairs   (tone="dark", défaut)
      public/images/brand/logo-light.png  → logo blanc, pour fonds sombres (tone="light")
    Ratio 728×377 conservé : ne fixer QUE la hauteur (classe h-*), la largeur suit.
--}}
@props(['tone' => 'dark'])

@php
    $file = $tone === 'light' ? 'images/brand/logo-light.png' : 'images/brand/logo.png';
    // Hauteur par défaut uniquement si l'appelant n'en fournit pas (évite deux h-* concurrents).
    $classes = $attributes->get('class') ?: 'h-11';
@endphp

<img src="{{ asset($file) }}" alt="{{ config('yassami.brand') }}" width="728" height="377" decoding="async"
     {{ $attributes->except('class') }} class="w-auto shrink-0 object-contain object-left {{ $classes }}">
