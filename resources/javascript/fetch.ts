"use strict";

import { User } from "./model/User";
import { Role } from "./model/Role";

export default async function(
  method: string,
  url: string,
  data: any | null = null
): Promise<User | Role | Resource> {
  const init: RequestInit = {
    method
  };

  if (data !== null) {
    init.headers = {
      "Content-Type": "application/json"
    };
    init.body = JSON.stringify(data);
  }

  const response = await fetch(url, init);

  if (!response.ok) {
    throw new Error(response.statusText);
  }

  return response.json();
}
