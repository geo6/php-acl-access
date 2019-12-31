"use strict";

export default function(element: HTMLInputElement): number {
  const { id, max, value } = element;

  document.querySelector(
    `label[for="${id}"] > span`
  ).innerHTML = `${value}/${max}`;

  return parseInt(value);
}
