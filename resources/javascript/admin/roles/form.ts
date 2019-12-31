"use strict";

import { Role } from "../../model/Role";

import createRole from "../api/post";
import updateRole from "../api/put";

export async function submit(form: HTMLFormElement, id: number): Promise<Role> {
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
