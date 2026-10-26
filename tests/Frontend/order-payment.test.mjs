import test, { beforeEach } from 'node:test'
import assert from 'node:assert/strict'

let success, failed, launches, verifications, interruptions
beforeEach(async () => {
    launches = verifications = interruptions = 0
    globalThis.window = {
        openKkiapayWidget: () => { launches++ },
        addSuccessListener: (callback) => { success = callback },
        addFailedListener: (callback) => { failed = callback },
    }
    await import(`../../resources/js/order-payment.js?test=${Math.random()}`)
})
function component() {
    return Object.assign(window.tkOrderPayment({
        confirmOnline: async () => { verifications++ },
        widgetInterrupted: async () => { interruptions++ },
    }, { key: 'public-fictif', sandbox: true }), { $el: { isConnected: true } })
}
const detail = { reference: 'fictive', amount: 1000, orderNumber: 'FICTIF' }
const tick = () => new Promise((resolve) => setImmediate(resolve))

test('un double événement ouvre une seule fenêtre et un seul couple de listeners', async () => {
    const c = component()
    await c.open(detail)
    await c.open(detail)
    assert.equal(launches, 1)
    assert.equal(c.busy, true)
})
test('le succès doublé ne lance pas deux vérifications simultanées', async () => {
    const c = component()
    await c.open(detail)
    success({ transactionId: 'fictive' })
    success({ transactionId: 'fictive' })
    await tick()
    assert.equal(verifications, 1)
    assert.equal(c.busy, false)
})
test('la fermeture et un événement échec ne décident pas du statut bancaire', async () => {
    const c = component()
    await c.open(detail)
    await c.interrupted()
    await c.open(detail)
    failed()
    await tick()
    assert.equal(interruptions, 2)
    assert.equal(verifications, 0)
    assert.equal(c.busy, false)
})
test('une ancienne page démontée ne traite aucun résultat', async () => {
    const c = component()
    await c.open(detail)
    c.$el.isConnected = false
    success({ transactionId: 'fictive' })
    failed()
    await tick()
    assert.equal(verifications, 0)
    assert.equal(interruptions, 0)
})
test('un SDK indisponible conserve une sortie et autorise un nouvel essai', async () => {
    const c = component()
    window.openKkiapayWidget = () => { throw new Error('SDK fictif indisponible') }
    await c.open(detail)
    assert.equal(c.busy, false)
    assert.match(c.message, /commande est conservée/)
})
