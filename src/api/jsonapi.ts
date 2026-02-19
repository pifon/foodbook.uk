import type { AxiosResponse } from 'axios';

export interface JsonApiResource {
  type: string;
  id: string;
  attributes: Record<string, unknown>;
  relationships?: Record<
    string,
    {
      data: { type: string; id: string } | { type: string; id: string }[] | null;
    }
  >;
  links?: Record<string, string>;
}

export interface JsonApiDocument {
  data: JsonApiResource | JsonApiResource[];
  included?: JsonApiResource[];
  meta?: {
    page?: {
      'current-page': number;
      'per-page': number;
      'total': number;
      'last-page': number;
    };
    [key: string]: unknown;
  };
  links?: Record<string, string | null>;
}

/**
 * Flattens a JSON:API resource into a plain object with `id`, all attributes,
 * and resolved relationships (when `included` data is provided).
 */
export function deserializeResource(
  resource: JsonApiResource,
  included?: JsonApiResource[],
): Record<string, unknown> {
  const result: Record<string, unknown> = {
    id: resource.id,
    type: resource.type,
    ...resource.attributes,
  };

  if (resource.relationships && included) {
    for (const [key, rel] of Object.entries(resource.relationships)) {
      if (!rel.data) {
        result[key] = null;
        continue;
      }

      if (Array.isArray(rel.data)) {
        result[key] = rel.data
          .map((ref) => {
            const found = included.find((i) => i.type === ref.type && i.id === ref.id);
            return found ? deserializeResource(found, included) : { id: ref.id, type: ref.type };
          });
      } else {
        const ref = rel.data as { type: string; id: string };
        const found = included.find((i) => i.type === ref.type && i.id === ref.id);
        result[key] = found ? deserializeResource(found, included) : { id: ref.id, type: ref.type };
      }
    }
  }

  return result;
}

export function deserialize(doc: JsonApiDocument): {
  data: Record<string, unknown> | Record<string, unknown>[];
  meta?: JsonApiDocument['meta'];
  links?: JsonApiDocument['links'];
} {
  const included = doc.included;

  const data = Array.isArray(doc.data)
    ? doc.data.map((r) => deserializeResource(r, included))
    : deserializeResource(doc.data, included);

  return { data, meta: doc.meta, links: doc.links };
}

export function deserializeResponse(response: AxiosResponse<JsonApiDocument>) {
  return deserialize(response.data);
}
