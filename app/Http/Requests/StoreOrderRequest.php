<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    /** Produits visibles chargés pendant la validation, indexés par id. */
    private ?Collection $products = null;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $customer = (array) $this->input('customer', []);

        if (isset($customer['phone']) && is_string($customer['phone'])) {
            $customer['phone'] = preg_replace('/[\s.\-()]/', '', $customer['phone']);
        }

        foreach (['name', 'city', 'address'] as $field) {
            if (isset($customer[$field]) && is_string($customer[$field])) {
                $customer[$field] = trim(preg_replace('/\s+/', ' ', $customer[$field]));
            }
        }

        $this->merge(['customer' => $customer]);
    }

    public function rules(): array
    {
        $max = config('yassami.max_quantity_per_line');

        return [
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'min:2', 'max:120'],
            // Mobile/fixe marocain : 06…, 07…, 05…, +2126…, 002126…
            'customer.phone' => ['required', 'string', 'regex:/^(?:\+212|00212|0)[5-7]\d{8}$/'],
            'customer.city' => ['required', 'string', 'min:2', 'max:80'],
            'customer.address' => ['required', 'string', 'min:5', 'max:255'],

            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', "max:{$max}"],
            'items.*.size' => ['nullable', 'string', 'max:30'],
            'items.*.color' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $items = collect($this->input('items'));
            $products = $this->products();
            $requestedPerProduct = [];

            foreach ($items as $i => $item) {
                $product = $products->get((int) $item['product_id']);

                if (! $product) {
                    $validator->errors()->add("items.$i", 'Un article de votre panier n’est plus disponible. Retirez-le pour continuer.');

                    continue;
                }

                $sizes = $product->sizes ?? [];
                if ($sizes !== [] && ! in_array($item['size'] ?? null, $sizes, true)) {
                    $validator->errors()->add("items.$i", "Choisissez une taille valide pour « {$product->name} ».");
                }

                $colors = array_column($product->color_options, 'name');
                if ($colors !== [] && ! in_array($item['color'] ?? null, $colors, true)) {
                    $validator->errors()->add("items.$i", "Choisissez une couleur valide pour « {$product->name} ».");
                }

                $requestedPerProduct[$product->id] = ($requestedPerProduct[$product->id] ?? 0) + (int) $item['quantity'];
            }

            foreach ($requestedPerProduct as $productId => $quantity) {
                $product = $products->get($productId);
                if ($product->stock_quantity < $quantity) {
                    $validator->errors()->add('items', $product->stock_quantity === 0
                        ? "« {$product->name} » est en rupture de stock."
                        : "Il ne reste que {$product->stock_quantity} « {$product->name} » en stock.");
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'customer.name.required' => 'Indiquez votre nom complet.',
            'customer.name.min' => 'Votre nom semble trop court.',
            'customer.phone.required' => 'Indiquez votre numéro de téléphone.',
            'customer.phone.regex' => 'Numéro invalide. Exemple : 06 12 34 56 78.',
            'customer.city.required' => 'Indiquez votre ville.',
            'customer.address.required' => 'Indiquez votre adresse de livraison.',
            'customer.address.min' => 'Votre adresse semble incomplète.',
            'items.required' => 'Votre panier est vide.',
            'items.min' => 'Votre panier est vide.',
            'items.*.quantity.max' => 'Quantité maximale : :max par article.',
        ];
    }

    /** @return Collection<int, Product> */
    public function products(): Collection
    {
        return $this->products ??= Product::visible()
            ->whereIn('id', collect($this->input('items'))->pluck('product_id')->map(fn ($id) => (int) $id))
            ->get()
            ->keyBy('id');
    }
}
