import { OpenAI } from 'openai';

declare global {
  namespace NodeJS {
    interface ProcessEnv {
      OPENAI_API_KEY: string;
    }
  }
}

const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

export async function validateWithGPT(text: string) {
  try {
    const completion = await openai.chat.completions.create({
      model: "gpt-4",
      messages: [
        {
          role: "system",
          content: "Extract funeral notice details from the following text. Return a JSON object with name, dateOfDeath, funeralDate, location, and community fields. Return null if the text is not a funeral notice."
        },
        {
          role: "user",
          content: text
        }
      ]
    });

    const result = JSON.parse(completion.choices[0].message.content);
    return result;
  } catch (error) {
    console.error('GPT validation error:', error);
    return null;
  }
} 