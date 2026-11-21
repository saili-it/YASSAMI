<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Synchronise les photos d'un produit : suppression, réordonnancement, ajout.
 * Ordre final = images conservées (dans l'ordre demandé) puis nouvelles images.
 */
class ProductImageService
{
    public function __construct(private readonly ImageStorage $storage) {}

    /**
     * @param  array<int, UploadedFile>  $uploads
     * @param  array<int, int>  $order  ids des images existantes dans l'ordre souhaité
     * @param  array<int, int>  $remove  ids des images à supprimer
     */
    public function sync(Product $product, array $uploads = [], array $order = [], array $remove = []): void
    {
        // Fichiers écrits AVANT la transaction : en cas d'échec on les supprime.
        $newPaths = array_map(fn (UploadedFile $file) => $this->storage->store($file, "products/{$product->id}"), $uploads);
        $deletedPaths = [];

        try {
            DB::transaction(function () use ($product, $order, $remove, $newPaths, &$deletedPaths) {
                $images = $product->images()->get()->keyBy('id');
                $remove = array_map('intval', $remove);

                foreach ($images->only($remove) as $image) {
                    $deletedPaths[] = $image->path;
                    $image->delete();
                }

                $kept = $images->except($remove);
                // Ordre demandé d'abord, puis les éventuelles images non mentionnées.
                $orderedIds = collect($order)->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $kept->has($id))
                    ->merge($kept->keys())
                    ->unique()
                    ->values();

                foreach ($orderedIds as $position => $id) {
                    $kept[$id]->update(['sort_order' => $position]);
                }

                $position = $orderedIds->count();
                foreach ($newPaths as $path) {
                    $product->images()->create([
                        'path' => $path,
                        'alt' => $product->name,
                        'sort_order' => $position++,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            array_walk($newPaths, fn ($path) => $this->storage->delete($path));
            throw $e;
        }

        // Fichiers supprimés uniquement une fois la base à jour.
        array_walk($deletedPaths, fn ($path) => $this->storage->delete($path));
        $product->unsetRelation('images');
    }

    public function deleteAll(Product $product): void
    {
        foreach ($product->images()->pluck('path') as $path) {
            $this->storage->delete($path);
        }
    }
}
