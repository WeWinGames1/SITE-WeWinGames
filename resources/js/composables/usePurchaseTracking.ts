/**
 * Single-fire guard for purchase analytics events (GTM dataLayer, Reddit pixel,
 * X pixel).
 *
 * purchase_data is flashed to the session, which means more than one component
 * sees the same sale: the checkout page's form onSuccess callback runs, and so
 * does the onMounted hook of whatever page the server redirected to. Both then
 * push their own purchase event and every ad platform counts one sale twice —
 * and a GTM tag bound to the `purchase` dataLayer event doubles with them.
 *
 * Claim the purchase before reporting it:
 *
 *   if (!claimPurchase(purchaseData)) return;
 */

export interface PurchaseData {
    conversion_id?: string | null;
    plan_name?: string | null;
    plan_price?: number | string | null;
    billing_period?: string | null;
}

const STORAGE_KEY = 'wwg:reported-purchases';

/** Fallback when sessionStorage is unavailable (private mode, SSR, blocked). */
const claimed = new Set<string>();

/**
 * The PaymentIntent id is unique per charge. Purchases without one ($0 trials,
 * plan swaps) fall back to the plan they describe, so two such events for the
 * same plan in one session collapse into one — the safe direction to err for a
 * signal that is already duplicate-prone.
 */
function purchaseKey(purchase: PurchaseData): string {
    return purchase.conversion_id ?? `${purchase.plan_name}|${purchase.plan_price}|${purchase.billing_period}`;
}

function readStored(): string[] {
    try {
        const raw = window.sessionStorage.getItem(STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

/**
 * Returns true the first time a purchase is seen, false on every repeat — so
 * the caller reports it exactly once per browser session.
 */
export function claimPurchase(purchase: PurchaseData | null | undefined): boolean {
    if (!purchase) {
        return false;
    }

    const key = purchaseKey(purchase);

    if (claimed.has(key)) {
        return false;
    }

    claimed.add(key);

    if (typeof window === 'undefined') {
        return true;
    }

    try {
        const stored = readStored();

        if (stored.includes(key)) {
            return false;
        }

        // Bounded so a long session can't grow the entry without limit.
        window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify([...stored, key].slice(-20)));
    } catch {
        // sessionStorage unavailable — the in-memory Set still covers the
        // common case of two components reacting to one flashed purchase.
    }

    return true;
}
