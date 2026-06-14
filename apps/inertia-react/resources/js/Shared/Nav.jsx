import { Link, usePage } from '@inertiajs/react'

export default function Nav() {
  const nav = usePage().props.nav ?? []
  return (
    <header>
      <nav aria-label="Contract pages">
        <Link href="/" data-testid="nav-home">Home</Link>
        {nav.map((item) => (
          <Link key={item.key} href={item.href} data-testid={`nav-${item.key}`}>
            {item.label}
          </Link>
        ))}
      </nav>
    </header>
  )
}
