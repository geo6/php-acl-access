"use strict";

import { Resource } from "../../model/Resource";

import createResource from "../api/post";
import updateResource from "../api/put";

export async function submit(
  form: HTMLFormElement,
  id: number
): Promise<Resource> {
  const data = new FormData(form);

  const resource = new Resource();

  resource.name = data.get("name").toString();
  resource.path = data.get("path").toString();

  if (id !== null) {
    return updateResource(id, resource) as Promise<Resource>;
  } else {
    return createResource(resource) as Promise<Resource>;
  }
}
