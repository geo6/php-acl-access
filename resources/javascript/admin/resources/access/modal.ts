"use strict";

import $ from "jquery";

import { Resource } from "../../../model/Resource";
import { Role } from "../../../model/Role";
import request from "../../../global/fetch";

export async function load(apiAccessURL: string, id: number): Promise<void> {
  return request("GET", `${apiAccessURL}/resource/${id}`).then(
    (json: Array<{ role: Role; resource: Resource }>) => {
      document
        .querySelectorAll(
          "#modal-access-role input[type='radio'][value='0']"
        )
        .forEach((input: HTMLInputElement) => {
          input.checked = true;
        });

      json.forEach((rr: { role: Role; resource: Resource }) => {
        const input = document.querySelector(
          `#modal-access-role input[type='radio'][name='role[${rr.role.id}]'][value='1']`
        ) as HTMLInputElement;

        input.checked = true;
      });

      $("#modal-access-role").modal("show");
    }
  );
}
