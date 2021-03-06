import { Role } from "./Role";

export class User {
  id: number;
  login: string;
  email: string;
  fullname: string;
  password?: string;
  redirect: number;
  roles: Role[];
}
