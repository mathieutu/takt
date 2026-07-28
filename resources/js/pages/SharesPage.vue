<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { useToast } from '@nuxt/ui/composables'
import { useConfirm } from '@/composables/useConfirm.ts'
import { useFlash } from '@/composables/useFlash'
import { formatDate } from '@/utils/date'
import billing from '@/wayfinder/routes/clients/billing'
import { destroy as destroyClientShare, store as storeClientShare } from '@/wayfinder/routes/clients/share'
import { destroy as destroyShare, show as showShare } from '@/wayfinder/routes/shares'

type ReceivedShare = {
  id: string,
  token: string,
  client_name: string,
  owner_name: string,
  is_valid: boolean,
  saved_at: string,
}

type Follower = {
  id: string,
  user_name: string,
  is_valid: boolean,
  saved_at: string,
}

type MyShare = {
  id: string,
  name: string,
  is_shared: boolean,
  share_url: string | null,
  followers: Follower[],
}

defineProps<{
  received_shares: ReceivedShare[],
  my_shares: MyShare[],
}>()

useFlash()

const toast = useToast()

const copyUrl = async (url: string) => {
  await navigator.clipboard.writeText(url)
  toast.add({ title: 'Lien copié dans le presse-papier', color: 'success' })
}

const confirm = useConfirm()

const removeShare = (share: ReceivedShare) => {
  confirm({
    title: `Retirer "${share.client_name}" de mes partages ?`,
    onConfirm: () => router.visit(destroyShare(share.id), { preserveScroll: true }),
  })
}

const followerTooltip = (follower: Follower): string =>
  `${follower.is_valid ? 'À jour' : 'À resynchroniser'} · sauvegardé le ${formatDate(follower.saved_at, true)}`

const regenerateShare = (client: MyShare) => {
  router.visit(storeClientShare(client.id), { preserveScroll: true, only: ['my_shares'] })
}

const revokeShare = (client: MyShare) => {
  confirm({
    title: `Révoquer le lien de partage pour "${client.name}" ?`,
    description: 'Quiconque dispose du lien perdra immédiatement l\'accès.',
    onConfirm: () => router.visit(destroyClientShare(client.id), { preserveScroll: true, only: ['my_shares'] }),
  })
}
</script>

<template>
  <Head title="Partages" />
  <main class="flex-1 px-6 py-8">
    <div class="mx-auto max-w-2xl space-y-8">
      <div>
        <h1 class="text-lg font-semibold">Partages</h1>
        <p class="text-sm text-muted">Gérez les liens de partage reçus et suivis</p>
      </div>

      <section class="space-y-3">
        <h2 class="text-sm font-semibold">Partages reçus</h2>

        <p v-if="received_shares.length === 0" class="text-sm text-muted">
          Aucun partage sauvegardé pour le moment.
        </p>
        <ul v-else class="divide-y divide-default">
          <li v-for="share in received_shares" :key="share.id">
            <component
              :is="share.is_valid ? Link : 'div'"
              :href="share.is_valid ? showShare(share.token) : undefined"
              class="flex items-center justify-between gap-4 rounded-md px-2 py-3 -mx-2 transition-colors"
              :class="share.is_valid ? 'hover:bg-muted/40' : 'opacity-60'"
            >
              <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ share.client_name }}</p>
                <p class="text-xs text-muted truncate">Partagé par {{ share.owner_name }}</p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span class="text-xs text-muted">{{ formatDate(share.saved_at) }}</span>
                <UBadge
                  :label="share.is_valid ? 'Actif' : 'Révoqué'"
                  :color="share.is_valid ? 'success' : 'error'"
                  variant="subtle"
                  size="sm"
                />
                <UButton
                  icon="i-lucide-trash-2"
                  color="error"
                  variant="ghost"
                  size="sm"
                  aria-label="Retirer"
                  @click.stop="removeShare(share)"
                />
              </div>
            </component>
          </li>
        </ul>
      </section>

      <section class="space-y-3">
        <h2 class="text-sm font-semibold">Mes partages</h2>

        <p v-if="my_shares.length === 0" class="text-sm text-muted">
          Aucun client partagé pour le moment.
        </p>
        <ul v-else class="divide-y divide-default">
          <li v-for="client in my_shares" :key="client.id" class="py-3">
            <Link
              :href="billing.show(client.id)"
              class="flex items-center justify-between gap-4 rounded-md px-2 -mx-2 py-1 hover:bg-muted/40 transition-colors"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium truncate">{{ client.name }}</p>
                  <UBadge v-if="!client.is_shared" label="Révoqué" color="error" variant="subtle" size="sm" />
                </div>
                <p class="text-xs text-muted truncate">Lien de partage</p>
              </div>
              <div v-if="client.is_shared" class="flex shrink-0 items-center gap-1">
                <UButton
                  icon="i-lucide-copy"
                  label="Copier le lien"
                  color="neutral"
                  variant="ghost"
                  size="xs"
                  @click.stop.prevent="copyUrl(client.share_url!)"
                />
                <UButton
                  icon="i-lucide-link-2-off"
                  label="Révoquer"
                  color="error"
                  variant="ghost"
                  size="xs"
                  @click.stop.prevent="revokeShare(client)"
                />
              </div>
              <UButton
                v-else
                icon="i-lucide-refresh-cw"
                label="Régénérer"
                color="neutral"
                variant="ghost"
                size="xs"
                class="shrink-0"
                @click.stop.prevent="regenerateShare(client)"
              />
            </Link>

            <p v-if="!client.is_shared" class="mt-1 text-xs text-muted">
              Régénérer crée un nouveau lien : l'ancien ne fonctionnera plus, pensez à renvoyer
              le nouveau aux personnes concernées.
            </p>

            <p v-if="client.followers.length === 0" class="mt-1.5 text-xs text-muted">
              Personne n'a encore sauvegardé ce lien.
            </p>
            <div v-else-if="client.is_shared" class="mt-1.5 flex flex-wrap gap-1.5">
              <UTooltip
                v-for="follower in client.followers"
                :key="follower.id"
                :text="followerTooltip(follower)"
              >
                <UBadge
                  :label="follower.user_name"
                  :color="follower.is_valid ? 'success' : 'neutral'"
                  variant="subtle"
                  size="sm"
                />
              </UTooltip>
            </div>
            <p v-else class="mt-1.5 text-xs text-muted">
              Sauvegardé par {{ client.followers.map(f => f.user_name).join(', ') }}
            </p>
          </li>
        </ul>
      </section>
    </div>
  </main>
</template>
