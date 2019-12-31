"use strict";

import { Resource } from "../../model/Resource";

export function load(resource: Resource): void {
  const form = document.querySelector(
    "#modal-resource form"
  ) as HTMLFormElement;

  form.reset();

  (form.querySelector("input[name='name']") as HTMLInputElement).value =
    resource.name;

  (form.querySelector("input[name='path']") as HTMLInputElement).value =
    resource.path;
}
