import { Pool, type PoolClient } from 'pg';
import { DATABASE_URL } from './env.js';

export const pool = new Pool({
  connectionString: DATABASE_URL,
  max: 10,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 5000,
});

async function connectWithRetry(attempts = 15): Promise<PoolClient> {
  let lastError: unknown;
  for (let i = 1; i <= attempts; i += 1) {
    try {
      return await pool.connect();
    } catch (error) {
      lastError = error;
      const waitMs = Math.min(1000 * i, 4000);
      console.error(`Database connect attempt ${i}/${attempts} failed; retrying in ${waitMs}ms`);
      await new Promise((resolve) => setTimeout(resolve, waitMs));
    }
  }
  throw lastError;
}

export async function initializeDatabase(): Promise<void> {
  console.log(`Connecting to ${DATABASE_URL.replace(/:[^:@/]+@/, ':***@')}`);
  const client = await connectWithRetry();

  try {
    await client.query('CREATE EXTENSION IF NOT EXISTS pgcrypto;');

    await client.query(`
      CREATE TABLE IF NOT EXISTS visitors (
        id UUID PRIMARY KEY,
        token VARCHAR(120) UNIQUE NOT NULL,
        name VARCHAR(120),
        current_page VARCHAR(255) NOT NULL DEFAULT '/visitor/demo',
        status VARCHAR(32) NOT NULL DEFAULT 'connected',
        ip VARCHAR(64),
        location VARCHAR(255),
        browser VARCHAR(80),
        device VARCHAR(80),
        started_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        last_seen_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        metadata JSONB NOT NULL DEFAULT '{}'::jsonb
      );
    `);

    await client.query(`
      CREATE TABLE IF NOT EXISTS visitor_activity (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        visitor_id UUID NOT NULL REFERENCES visitors(id) ON DELETE CASCADE,
        event_type VARCHAR(80) NOT NULL,
        payload JSONB NOT NULL DEFAULT '{}'::jsonb,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      );
    `);

    await client.query(`
      CREATE TABLE IF NOT EXISTS admin_settings (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        key_name VARCHAR(80) UNIQUE NOT NULL,
        value JSONB NOT NULL DEFAULT '{}'::jsonb,
        updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      );
    `);

    await client.query(`
      CREATE TABLE IF NOT EXISTS admin_keys (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        admin_key VARCHAR(255) UNIQUE NOT NULL,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        active BOOLEAN NOT NULL DEFAULT true
      );
    `);

    await client.query(`
      CREATE INDEX IF NOT EXISTS idx_visitors_status
      ON visitors (status, last_seen_at DESC);
    `);

    await client.query(`
      CREATE INDEX IF NOT EXISTS idx_visitor_activity_visitor
      ON visitor_activity (visitor_id, created_at DESC);
    `);

    await client.query(`
      ALTER TABLE visitors
      ADD COLUMN IF NOT EXISTS case_number VARCHAR(32);
    `);
    await client.query(`
      ALTER TABLE visitors
      ADD COLUMN IF NOT EXISTS last_message TEXT;
    `);
    await client.query(`
      ALTER TABLE visitors
      ADD COLUMN IF NOT EXISTS contact_name VARCHAR(120);
    `);
    await client.query(`
      ALTER TABLE visitors
      ADD COLUMN IF NOT EXISTS contact_email VARCHAR(200);
    `);
    await client.query(`
      ALTER TABLE visitors
      ADD COLUMN IF NOT EXISTS public_address VARCHAR(120);
    `);

    await client.query(`
      CREATE TABLE IF NOT EXISTS support_messages (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        visitor_id UUID NOT NULL REFERENCES visitors(id) ON DELETE CASCADE,
        author_role VARCHAR(16) NOT NULL,
        body TEXT NOT NULL,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      );
    `);

    await client.query(`ALTER TABLE visitors ADD COLUMN IF NOT EXISTS case_status VARCHAR(40) DEFAULT 'OPEN';`);
    await client.query(`ALTER TABLE visitors ADD COLUMN IF NOT EXISTS ended_at TIMESTAMPTZ;`);
    await client.query(`ALTER TABLE visitors ADD COLUMN IF NOT EXISTS display_name VARCHAR(120);`);
    await client.query(`ALTER TABLE visitors ADD COLUMN IF NOT EXISTS demo_account_id VARCHAR(120);`);
    await client.query(`ALTER TABLE visitors ADD COLUMN IF NOT EXISTS support_reference VARCHAR(120);`);
    await client.query(`ALTER TABLE visitors ADD COLUMN IF NOT EXISTS issue_description TEXT;`);
    await client.query(`ALTER TABLE visitors ADD COLUMN IF NOT EXISTS selected_product VARCHAR(120);`);
    await client.query(`ALTER TABLE visitors ADD COLUMN IF NOT EXISTS display_value VARCHAR(200);`);
    await client.query(`ALTER TABLE visitors ADD COLUMN IF NOT EXISTS test_message TEXT;`);
    await client.query(`ALTER TABLE visitors ADD COLUMN IF NOT EXISTS user_agent VARCHAR(255);`);

    await client.query(`
      CREATE TABLE IF NOT EXISTS internal_notes (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        visitor_id UUID NOT NULL REFERENCES visitors(id) ON DELETE CASCADE,
        body TEXT NOT NULL,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      );
    `);

    await client.query(`
      INSERT INTO admin_settings (key_name, value)
      VALUES (
        'panel',
        '{"enabled":true,"notifications":true,"startingPage":"/welcome","sessionTimeoutMinutes":30,"sound":true,"theme":"dark"}'::jsonb
      )
      ON CONFLICT (key_name) DO NOTHING;
    `);
  } finally {
    client.release();
  }
}
