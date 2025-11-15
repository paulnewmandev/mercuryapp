#!/usr/bin/env python3
"""Parse the Excel report and produce JSON payloads for seeders."""
from __future__ import annotations

import json
from pathlib import Path

import pandas as pd

BASE_DIR = Path(__file__).resolve().parent.parent
EXCEL_PATH = BASE_DIR / 'Especialista Mac - Reporte de productos.xlsx'
OUTPUT_DIR = BASE_DIR / 'database' / 'seeders' / 'data'
PRODUCTS_OUTPUT = OUTPUT_DIR / 'products.json'
CATEGORIES_OUTPUT = OUTPUT_DIR / 'product_categories.json'

REQUIRED_COLUMNS = [
    'Cod. producto',
    'Cod. barra',
    'Nombre*',
    'Precio costo',
    'Ganancia (%)',
    'Iva (%)',
    'Categoría*',
    'Subcategoría',
    'Stock',
    'P. s/iva',
    'Precio venta',
]


def load_dataframe() -> pd.DataFrame:
    if not EXCEL_PATH.exists():
        raise SystemExit(f'Excel file not found at {EXCEL_PATH}')

    df = pd.read_excel(EXCEL_PATH, header=1)
    missing = [col for col in REQUIRED_COLUMNS if col not in df.columns]
    if missing:
        raise SystemExit(f'Missing expected columns in Excel file: {missing}')

    df = df[REQUIRED_COLUMNS]
    df = df[df['Cod. producto'].notna()]
    df = df.reset_index(drop=True)
    return df


def normalize_string(value) -> str | None:
    if pd.isna(value):
        return None
    text = str(value).strip()
    return text or None


def to_int(value) -> int:
    if pd.isna(value):
        return 0
    try:
        return int(float(value))
    except (ValueError, TypeError):
        return 0


def to_float(value) -> float:
    if pd.isna(value):
        return 0.0
    try:
        return round(float(value), 4)
    except (ValueError, TypeError):
        return 0.0


def build_payload(df: pd.DataFrame) -> tuple[list[dict], dict]:
    products: list[dict] = []
    categories_map: dict[str, set[str]] = {}

    for row in df.to_dict(orient='records'):
        sku = normalize_string(row.get('Cod. producto'))
        if not sku:
            continue
        barcode = normalize_string(row.get('Cod. barra'))
        name = normalize_string(row.get('Nombre*')) or sku
        category = normalize_string(row.get('Categoría*')) or 'Sin categoría'
        subcategory = normalize_string(row.get('Subcategoría'))

        categories_map.setdefault(category, set())
        if subcategory:
            categories_map[category].add(subcategory)

        product = {
            'sku': sku,
            'barcode': barcode or sku,
            'name': name,
            'category': category,
            'subcategory': subcategory,
            'stock': to_int(row.get('Stock')),
            'price_cost': to_float(row.get('Precio costo')),
            'price_without_tax': to_float(row.get('P. s/iva')),
            'price_sale': to_float(row.get('Precio venta')),
            'iva_percentage': to_float(row.get('Iva (%)')),
            'profit_percentage': to_float(row.get('Ganancia (%)')),
        }
        products.append(product)

    categories_payload = {
        category: sorted(subs)
        for category, subs in sorted(categories_map.items(), key=lambda item: item[0].lower())
    }

    return products, categories_payload


def main() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    df = load_dataframe()
    products, categories = build_payload(df)

    PRODUCTS_OUTPUT.write_text(
        json.dumps(products, ensure_ascii=False, indent=2),
        encoding='utf-8',
    )
    CATEGORIES_OUTPUT.write_text(
        json.dumps(categories, ensure_ascii=False, indent=2),
        encoding='utf-8',
    )

    print(f'Generated {len(products)} products -> {PRODUCTS_OUTPUT}')
    print(f'Generated categories -> {CATEGORIES_OUTPUT}')


if __name__ == '__main__':
    main()
