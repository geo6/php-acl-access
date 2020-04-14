"use strict";

import { User } from "../model/User";
import { Role } from "../model/Role";
import { Resource } from "../model/Resource";
import { FormError } from "./FormError";

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
  const json = await response.json();

  if (!response.ok) {
    if (typeof json.field !== "undefined") {
      throw new FormError(json.field, json.error || response.statusText);
    } else {
      throw new Error(json.error || response.statusText);
    }
  }

  return json;
}
