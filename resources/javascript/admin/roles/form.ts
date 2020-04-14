"use strict";

import { Role } from "../../model/Role";

import createRole from "../api/post";
import updateRole from "../api/put";

export function resetWarning(form: HTMLFormElement): void {
  (form.querySelector(".alert") as HTMLDivElement).hidden = true;

  form.querySelectorAll(".form-control.is-invalid").forEach((element: HTMLInputElement|HTMLSelectElement) => element.classList.remove("is-invalid"));
  form.querySelectorAll(".invalid-feedback").forEach((element: HTMLDivElement) => element.remove());
}

export async function submit(form: HTMLFormElement, id: number): Promise<Role> {
  resetWarning(form);

  const data = new FormData(form);

  const role = new Role();

  role.name = data.get("name").toString();
  role.priority = parseInt(data.get("priority").toString());

  if (id !== null) {
    return updateRole(id, role) as Promise<Role>;
  } else {
    return createRole(role) as Promise<Role>;
  }
}
