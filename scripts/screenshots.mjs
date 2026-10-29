/**
 * Gera as imagens do README (docs/screenshots/) a partir da conta de demonstração.
 *
 * Pré-requisitos: app rodando (./setup.sh, DEMO_MODE=true) e o Playwright instalado sem
 * alterar o package.json:
 *   npm i --no-save playwright
 *
 * Rodando pela imagem oficial do Playwright (já traz o Chromium e as bibliotecas do sistema):
 *   docker run --rm --network host --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD":/work -w /work \
 *     mcr.microsoft.com/playwright:v$(node -p "require('playwright/package.json').version")-noble node scripts/screenshots.mjs
 *
 * Ou direto na máquina: npx playwright install --with-deps chromium && node scripts/screenshots.mjs
 */
import { chromium } from 'playwright';

const baseUrl = process.env.APP_URL ?? 'http://localhost:8000';
const outDir = new URL('../docs/screenshots/', import.meta.url);

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1, locale: 'pt-BR' });

const shot = async (name, path, { fullPage = false } = {}) => {
    await page.goto(baseUrl + path, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready); // Fraunces/Figtree vêm do fonts.bunny.net
    await page.screenshot({ path: new URL(`${name}.png`, outDir).pathname, fullPage });
    console.log(`✓ ${name}.png`);
};

// Página inicial (visitante)
await shot('landing', '/');

// Entra pela demonstração
await page.getByRole('button', { name: 'Ver demonstração' }).first().click();
await page.waitForURL('**/finance/dashboard');

await shot('painel', '/finance/dashboard', { fullPage: true });
await shot('faturas', '/finance/bills');
await shot('carteira', '/investment/investments');
await shot('transacoes', '/finance/transactions');

await browser.close();
