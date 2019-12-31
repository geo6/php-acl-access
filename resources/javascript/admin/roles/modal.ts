"use strict";

import { Role } from "../../model/Role";

import rangeOnChange from "./range";

export function load(role: Role): void {
  const form = document.querySelector("#modal-role form") as HTMLFormElement;

  form.reset();

  (form.querySelector("input[name='name']") as HTMLInputElement).value =
    role.name;

  const priorityInput = form.querySelector(
    "input[name='priority']"
  ) as HTMLInputElement;

  priorityInput.value = role.priority.toString();

  rangeOnChange(priorityInput);
}
