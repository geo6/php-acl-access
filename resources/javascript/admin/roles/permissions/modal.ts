"use strict";

import $ from "jquery";

import { Resource } from "../../../model/Resource";
import { Role } from "../../../model/Role";
import request from "../../../fetch";

export async function load(apiAccessURL: string, id: number): Promise<void> {
  return request("GET", `${apiAccessURL}/role/${id}`).then(
    (json: Array<{ role: Role; resource: Resource }>) => {
      document
        .querySelectorAll(
          "#modal-permission-resource input[type='radio'][value='0']"
        )
        .forEach((input: HTMLInputElement) => {
          input.checked = true;
        });

      json.forEach((rr: { role: Role; resource: Resource }) => {
        const input = document.querySelector(
          `#modal-permission-resource input[type='radio'][name='resource[${rr.resource.id}]'][value='1']`
        ) as HTMLInputElement;

        input.checked = true;
      });

      $("#modal-permission-resource").modal("show");
    }
  );
}
