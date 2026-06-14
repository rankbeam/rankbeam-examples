import { Head } from '@inertiajs/react'
import Nav from '../Shared/Nav'

export default function Home({ title }) {
  return (
    <>
      <Head title={title} />
      <Nav />
      <main>
        <h1 data-testid="page-title">{title}</h1>
        <p>Navigate to a contract page above — Inertia handles the visit client-side.</p>
      </main>
    </>
  )
}
