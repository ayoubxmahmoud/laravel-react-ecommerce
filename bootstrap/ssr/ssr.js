import { createServer } from 'vite'

createServer({
  ssr: {
    noExternal: ['@inertiajs/server'],
  }
}).then(server => {
  server.listen(13714)
})
