import Queue from 'bull';
import { Scraper } from './scraper.server';

declare global {
  namespace NodeJS {
    interface ProcessEnv {
      REDIS_URL: string;
      FACEBOOK_GROUP_ID: string;
      FACEBOOK_ACCESS_TOKEN: string;
    }
  }
}

const scrapingQueue = new Queue('scraping', process.env.REDIS_URL);

export function initializeScheduler() {
  // Run every 6 hours
  scrapingQueue.add(
    'scrape-all-sources',
    {},
    {
      repeat: {
        every: 6 * 60 * 60 * 1000,
      },
    }
  );

  scrapingQueue.process('scrape-all-sources', async (job) => {
    const scraper = new Scraper();
    
    // Scrape Facebook groups
    await scraper.scrapeFacebook(
      process.env.FACEBOOK_GROUP_ID,
      process.env.FACEBOOK_ACCESS_TOKEN
    );

    // Scrape static websites
    await scraper.scrapeStaticWebsite('https://example.com/notices');
  });
} 