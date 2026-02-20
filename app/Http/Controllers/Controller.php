<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;

abstract class Controller
{
    public function __construct(
        protected readonly ApiClient $api,
    ) {
    }

    protected function token(): ?string
    {
        return session('api_token');
    }

    /**
     * Flatten a JSON:API resource into a plain array.
     */
    protected function flatten(array $resource, array $included = []): array
    {
        $result = ['id' => $resource['id'], 'type' => $resource['type']]
            + ($resource['attributes'] ?? []);

        foreach ($resource['relationships'] ?? [] as $key => $rel) {
            $data = $rel['data'] ?? null;
            if ($data === null) {
                $result[$key] = null;
                continue;
            }

            if (array_is_list($data)) {
                $result[$key] = array_map(
                    fn ($ref) => $this->resolveRef($ref, $included),
                    $data,
                );
            } else {
                $result[$key] = $this->resolveRef($data, $included);
            }
        }

        return $result;
    }

    protected function flattenCollection(array $document): array
    {
        $included = $document['included'] ?? [];
        $data = $document['data'] ?? [];

        return array_map(fn ($r) => $this->flatten($r, $included), $data);
    }

    protected function flattenSingle(array $document): array
    {
        return $this->flatten(
            $document['data'],
            $document['included'] ?? [],
        );
    }

    private function resolveRef(array $ref, array $included): array
    {
        foreach ($included as $item) {
            if ($item['type'] === $ref['type'] && $item['id'] === $ref['id']) {
                return $this->flatten($item, $included);
            }
        }

        return ['id' => $ref['id'], 'type' => $ref['type']];
    }
}
