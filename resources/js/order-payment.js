// Le navigateur décrit l'interaction ; seul le serveur confirme le paiement.
window.tkOrderPayment = (wire, config) => ({
    busy: false,
    reference: null,
    message: '',
    listening: false,
    verifying: false,
    async open(detail) {
        if (this.busy) return
        this.busy = true
        this.reference = detail.reference
        this.message = ''
        try {
            if (!window.openKkiapayWidget) {
                window.tkKkiapayLoading ??= new Promise((resolve, reject) => {
                    const script = document.createElement('script')
                    script.src = 'https://cdn.kkiapay.me/k.js'
                    script.onload = resolve
                    script.onerror = () => { script.remove(); window.tkKkiapayLoading = null; reject(new Error('sdk')) }
                    document.head.appendChild(script)
                })
                await window.tkKkiapayLoading
            }
            if (!this.listening) {
                window.addSuccessListener((response) => {
                    if (!this.$el.isConnected || !this.reference || !response?.transactionId || this.verifying) return
                    this.verifying = true
                    this.message = 'Vérification du paiement…'
                    wire.confirmOnline(this.reference, response.transactionId)
                        .then(() => { this.message = '' })
                        .catch(() => { this.message = 'La vérification est interrompue. Consultez la commande avant de réessayer. Si vous avez été débité, contactez l’équipe.' })
                        .finally(() => { this.busy = false; this.verifying = false })
                })
                window.addFailedListener(() => {
                    if (this.$el.isConnected && this.reference) this.interrupted()
                })
                this.listening = true
            }
            window.openKkiapayWidget({ amount: detail.amount, key: config.key, sandbox: config.sandbox,
                name: detail.name, phone: detail.phone,
                data: JSON.stringify({ reference: detail.reference, order: detail.orderNumber }) })
        } catch {
            this.busy = false
            this.message = 'La fenêtre de paiement est indisponible. Votre commande est conservée. Réessayez avant l’échéance ou contactez l’équipe.'
        }
    },
    async interrupted() {
        this.busy = false
        try { await wire.widgetInterrupted() }
        catch { this.message = 'Consultez la commande avant de réessayer. Si vous avez été débité, contactez l’équipe.' }
    },
})
