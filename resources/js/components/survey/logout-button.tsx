import { Link, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { logout } from '@/routes/alumni';

/** "Log out" for a logged-in graduate. Shows nothing for everyone else. */
export default function LogoutButton() {
    const { alumni } = usePage().props;

    if (!alumni) {
        return null;
    }

    return (
        <Link
            href={logout()}
            as="button"
            className="sv-logout"
            data-test="alumni-logout"
        >
            <LogOut size={14} aria-hidden />
            Log out
        </Link>
    );
}
