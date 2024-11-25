import type { LoaderFunction } from "@remix-run/node";
import { json } from "@remix-run/node";
import { useLoaderData } from "@remix-run/react";
import { prisma } from "~/db.server";
import * as React from 'react';
import type { FuneralNotice } from "@prisma/client";

interface LoaderData {
  notices: FuneralNotice[];
}

export const loader: LoaderFunction = async ({ request }) => {
  const url = new URL(request.url);
  const location = url.searchParams.get("location");
  const community = url.searchParams.get("community");
  
  const notices = await prisma.funeralNotice.findMany({
    where: {
      ...(location && { location }),
      ...(community && { community }),
    },
    orderBy: {
      funeralDate: 'asc',
    },
  });

  return json({ notices });
};

export default function Index() {
  const { notices } = useLoaderData<typeof loader>();

  return (
    <div className="container mx-auto px-4">
      <h1 className="text-3xl font-bold mb-6">Indigenous Funeral Notices</h1>
      
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {notices.map((notice) => (
          <div key={notice.id} className="border p-4 rounded-lg">
            <h2 className="text-xl font-semibold">{notice.name}</h2>
            <p className="text-gray-600">
              Funeral Date: {notice.funeralDate?.toLocaleDateString()}
            </p>
            <p className="text-gray-600">Location: {notice.location}</p>
            <p className="text-gray-600">Community: {notice.community}</p>
            {notice.imageUrl && (
              <img
                src={notice.imageUrl}
                alt="Funeral Notice"
                className="mt-4 rounded"
              />
            )}
          </div>
        ))}
      </div>
    </div>
  );
} 