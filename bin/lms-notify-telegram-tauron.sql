-- Dodaj kolumnę telegram_notify do tabeli alfa_plugin_tauron
ALTER TABLE "public"."alfa_plugin_tauron" 
ADD COLUMN IF NOT EXISTS "telegram_notify" boolean DEFAULT false;


