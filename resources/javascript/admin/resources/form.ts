"use strict";

import { Resource } from "../../model/Resource";

import createResource from "../api/post";
import updateResource from "../api/put";

export function resetWarning(form: HTMLFormElement): void {
  (form.querySelector(".alert") as HTMLDivElement).hidden = true;

  form.querySelectorAll(".form-control.is-invalid").forEach((element: HTMLInputElement|HTMLSelectElement) => element.classList.remove("is-invalid"));
  form.querySelectorAll(".invalid-feedback").forEach((element: HTMLDivElement) => element.remove());
}

export async function submit(form: HTMLFormElement, id: number): Promise<Resource> {
  resetWarning(form);

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
