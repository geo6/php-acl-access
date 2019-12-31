"use strict";

import { Resource } from "../../model/Resource";
import { Role } from "../../model/Role";
import { User } from "../../model/User";

import request from "../../fetch";

export default async function(id: number): Promise<Resource | Role | User> {
  return request("GET", `${apiURL}/${id}`);
}
