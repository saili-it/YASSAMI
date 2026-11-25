{{--
    Champs client du formulaire de commande (panier ET fiche produit).
    À placer dans un composant Alpine « checkoutForm » (customer, errors, fieldError).
--}}
@php
    $fields = [
        ['name' => 'name', 'label' => 'Nom complet', 'type' => 'text', 'autocomplete' => 'name', 'placeholder' => 'Prénom et nom'],
        ['name' => 'phone', 'label' => 'Téléphone', 'type' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '06 12 34 56 78'],
        ['name' => 'city', 'label' => 'Ville', 'type' => 'text', 'autocomplete' => 'address-level2', 'placeholder' => 'Ex. Casablanca'],
    ];
@endphp

@foreach ($fields as $f)
    <div>
        <label for="checkout-{{ $f['name'] }}" class="field-label">{{ $f['label'] }}</label>
        <input id="checkout-{{ $f['name'] }}" type="{{ $f['type'] }}" autocomplete="{{ $f['autocomplete'] }}" placeholder="{{ $f['placeholder'] }}"
               x-model.trim="customer.{{ $f['name'] }}" @input="delete errors['customer.{{ $f['name'] }}']"
               @if ($f['type'] === 'tel') inputmode="tel" @endif
               class="field" :class="fieldError('{{ $f['name'] }}') && 'field-invalid'" :data-invalid="!!fieldError('{{ $f['name'] }}')"
               :aria-invalid="!!fieldError('{{ $f['name'] }}')" aria-describedby="checkout-{{ $f['name'] }}-error" required>
        <p id="checkout-{{ $f['name'] }}-error" class="field-error" x-show="fieldError('{{ $f['name'] }}')" x-text="fieldError('{{ $f['name'] }}')"></p>
    </div>
@endforeach

<div>
    <label for="checkout-address" class="field-label">Adresse de livraison</label>
    <textarea id="checkout-address" rows="3" autocomplete="street-address" placeholder="Rue, numéro, quartier, repère…"
              x-model.trim="customer.address" @input="delete errors['customer.address']"
              class="field resize-none" :class="fieldError('address') && 'field-invalid'" :data-invalid="!!fieldError('address')"
              :aria-invalid="!!fieldError('address')" aria-describedby="checkout-address-error" required></textarea>
    <p id="checkout-address-error" class="field-error" x-show="fieldError('address')" x-text="fieldError('address')"></p>
</div>
