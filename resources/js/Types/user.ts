export interface User {
  id: number;
  username: string;
  name: string;
  email: string;
  email_verified_at: string | null;
  role: string;
  status: string;
  member_id?: number;
  nta?: number;
  is_juru_uang?: boolean;
}
