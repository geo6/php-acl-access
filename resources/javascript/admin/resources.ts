"use strict";

import $ from "jquery";

import "bootstrap/js/dist/modal";
import "bootstrap/js/dist/util";

import getResource from "./api/get";
import deleteResource from "./api/delete";
import { load as loadResourceInModal } from "./resources/modal";
import { Resource } from "../model/Resource";
import { submit as submitForm } from "./resources/form";

((): void => {
  let currentResourceId: number = null;

  /**
   * Create a new resource
   */
  document.getElementById("btn-create").addEventListener("click", () => {
    const form = document.querySelector(
      "#modal-resource form"
    ) as HTMLFormElement;

    form.reset();

    document.getElementById("btn-delete").setAttribute("hidden", "");

    $("#modal-resource").modal("show");
  });

  /**
   * Update a resource
   */
  document
    .querySelectorAll("a[href='#edit']")
    .forEach((element: HTMLLinkElement) => {
      element.addEventListener("click", (event: Event) => {
        const target = event.currentTarget as HTMLLinkElement;
        const { id } = target.closest("tr").dataset;

        event.preventDefault();

        currentResourceId = parseInt(id);

        getResource(parseInt(id)).then((json: Resource) => {
          loadResourceInModal(json);

          document.getElementById("btn-delete").removeAttribute("hidden");

          $("#modal-resource").modal("show");
        });
      });
    });

  /**
   * Delete a resource
   */
  document.getElementById("btn-delete").addEventListener("click", () => {
    if (
      window.confirm("Are you sure you want to delete this resource ?") === true
    ) {
      deleteResource(currentResourceId).then(() => {
        currentResourceId = null;

        document.location.reload();
      });
    }
  });

  /**
   * Submit form
   */
  document
    .querySelector("#modal-resource form")
    .addEventListener("submit", (event: Event) => {
      const target = event.currentTarget as HTMLFormElement;

      event.preventDefault();

      submitForm(target, currentResourceId).then(() => {
        currentResourceId = null;

        document.location.reload();
      });
    });
})();
