import type { Browser } from 'puppeteer';
import { chromium } from 'puppeteer';
import type { CheerioAPI } from 'cheerio';
import * as cheerio from 'cheerio';
import { prisma } from '~/db.server';
import { validateWithGPT } from './gpt.server';
import type { FuneralNotice } from '@prisma/client';

interface ScrapedNotice {
  name: string;
  details: string;
}

interface ValidatedData {
  name: string;
  dateOfDeath?: Date;
  funeralDate?: Date;
  location?: string;
  community?: string;
  source: string;
  sourceUrl?: string;
  imageUrl?: string;
  details?: string;
}

export class Scraper {
  async scrapeFacebook(groupId: string, accessToken: string) {
    try {
      const response = await fetch(
        `https://graph.facebook.com/v13.0/${groupId}/feed?access_token=${accessToken}`
      );
      const data = await response.json();
      
      for (const post of data.data) {
        const validatedData = await validateWithGPT(post.message);
        if (validatedData) {
          await this.saveNotice({
            ...validatedData,
            source: 'facebook',
            sourceUrl: post.permalink_url,
          });
        }
      }
    } catch (error) {
      console.error('Facebook scraping error:', error);
    }
  }

  async scrapeStaticWebsite(url: string) {
    try {
      const response = await fetch(url);
      const html = await response.text();
      const $ = cheerio.load(html);
      
      // Implement site-specific scraping logic
      const notices = $('.funeral-notice').map((_, el) => {
        return {
          name: $(el).find('.name').text(),
          details: $(el).find('.details').text(),
        };
      }).get();

      for (const notice of notices) {
        const validatedData = await validateWithGPT(notice.details);
        if (validatedData) {
          await this.saveNotice({
            ...validatedData,
            source: 'website',
            sourceUrl: url,
          });
        }
      }
    } catch (error) {
      console.error('Static website scraping error:', error);
    }
  }

  private async saveNotice(data: ValidatedData): Promise<void> {
    // Check for duplicates based on name and date
    const existing = await prisma.funeralNotice.findFirst({
      where: {
        name: data.name,
        funeralDate: data.funeralDate,
      },
    });

    if (!existing) {
      await prisma.funeralNotice.create({
        data: {
          ...data,
          validated: true,
        },
      });
    }
  }
} 