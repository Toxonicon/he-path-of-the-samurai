-- Remove duplicates, keep one record (newest by inserted_at) per dataset_id
WITH ranked AS (
  SELECT id,
         dataset_id,
         ROW_NUMBER() OVER (PARTITION BY dataset_id ORDER BY COALESCE(updated_at, inserted_at) DESC) AS rn
  FROM osdr_items
  WHERE dataset_id IS NOT NULL
)
DELETE FROM osdr_items
WHERE id IN (SELECT id FROM ranked WHERE rn > 1);

-- Recreate unique index (safe if exists)
CREATE UNIQUE INDEX IF NOT EXISTS ux_osdr_dataset_id
  ON osdr_items(dataset_id) WHERE dataset_id IS NOT NULL;
