"use strict";

export class FormError extends Error {
  public readonly field: string;

  public constructor(field: string, ...params) {
    super(...params);

    if (Error.captureStackTrace) {
      Error.captureStackTrace(this, FormError);
    }

    this.name = "FormError";
    this.field = field;
  }
}

export function display(form: HTMLFormElement, error: FormError): void {
  const inputElement = form.querySelector(`input[name="${error.field}"], select[name="${error.field}"]`) as HTMLInputElement|HTMLSelectElement;

  const feedbackElement = document.createElement("div");
  feedbackElement.className = "invalid-feedback";
  feedbackElement.innerText = error.message;

  inputElement.parentElement.insertBefore(feedbackElement, inputElement.nextSibling);

  inputElement.classList.add("is-invalid");
}
