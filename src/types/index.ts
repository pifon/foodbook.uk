export interface User {
  id: string;
  username: string;
  email: string;
  'created-at'?: string;
  'updated-at'?: string;
}

export interface Recipe {
  id: string;
  type: string;
  title: string;
  slug: string;
  description: string | null;
  difficulty: string | null;
  status: 'draft' | 'published' | 'archived';
  serves: number | null;
  'prep-time-minutes': number | null;
  price: number | null;
  currency: string | null;
  'published-at': string | null;
  'created-at': string;
  'updated-at': string;
  author?: Author;
  cuisine?: Cuisine;
  ingredients?: Ingredient[];
  directions?: Direction[];
}

export interface Ingredient {
  id: string;
  type: string;
  amount: number | null;
  notes: string | null;
  position: number;
  product?: Product;
  measure?: Measure;
}

export interface Direction {
  id: string;
  type: string;
  'step-number': number;
  duration: number | null;
  notes: string | null;
  operation?: Operation;
  product?: Product;
}

export interface Operation {
  id: string;
  name: string;
  slug: string;
}

export interface Product {
  id: string;
  name: string;
  slug: string;
}

export interface Measure {
  id: string;
  name: string;
  abbreviation: string;
}

export interface Cuisine {
  id: string;
  name: string;
  slug: string;
  'parent-id': string | null;
}

export interface Author {
  id: string;
  name: string;
  slug: string;
  tier: 'free' | 'verified' | 'pro' | 'premium';
}

export interface DishType {
  id: string;
  name: string;
  slug: string;
}

export interface Collection {
  id: string;
  name: string;
  type: 'bag' | 'menu';
  description: string | null;
  'created-at': string;
  'updated-at': string;
  items?: CollectionItem[];
}

export interface CollectionItem {
  id: string;
  position: number | null;
  'scheduled-date': string | null;
  'meal-slot': string | null;
  notes: string | null;
  recipe?: Recipe;
}

export interface ShoppingList {
  id: string;
  name: string;
  status: 'active' | 'completed' | 'archived';
  'created-at': string;
  'updated-at': string;
  items?: ShoppingListItem[];
}

export interface ShoppingListItem {
  id: string;
  quantity: number | null;
  checked: boolean;
  product?: Product;
  measure?: Measure;
}

export interface PantryItem {
  id: string;
  quantity: number | null;
  'expiry-date': string | null;
  'best-before-date': string | null;
  'is-expired': boolean;
  'is-past-best-before': boolean;
  product?: Product;
  measure?: Measure;
}

export interface Plan {
  id: string;
  name: string;
  slug: string;
  'monthly-price': number;
  'yearly-price': number;
  features?: PlanFeature;
}

export interface PlanFeature {
  'max-collections': number;
  'max-shopping-lists': number;
  'max-pantry-items': number;
  'paid-recipes': boolean;
  'api-rate-limit': number;
}

export interface Rating {
  id: string;
  score: number;
  'created-at': string;
  user?: User;
}

export interface Comment {
  id: string;
  body: string;
  'parent-id': string | null;
  'created-at': string;
  user?: User;
}

export interface Pagination {
  'current-page': number;
  'per-page': number;
  'total': number;
  'last-page': number;
}
