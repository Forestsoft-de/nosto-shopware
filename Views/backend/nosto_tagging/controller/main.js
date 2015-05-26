/**
 * Copyright (c) 2015, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

Ext.define('Shopware.apps.NostoTagging.controller.Main', {
    /**
     * Extends the Enlight controller.
     * @string
     */
    extend: 'Enlight.app.Controller',

    /**
     * Settings for the controller.
     */
    settings: {
        postMessageOrigin: null
    },

    /**
     * Initializes the controller.
     *
     * @return void
     */
    init: function () {
        var me = this;
        me.showWindow();
        me.loadSettings();
        me.postMessageListener();
    },

    /**
     * Shows the main window.
     *
     * @return void
     */
    showWindow: function () {
        var me = this;
        me.accountStore = me.getStore('Account');
        me.mainWindow = me.getView('Main').create({
            accountStore: me.accountStore
        });
        me.mainWindow.show();
        me.mainWindow.setLoading(true);
        me.accountStore.load({
            callback: function(records, operation, success) {
                me.mainWindow.setLoading(false);
                if (success) {
                    me.mainWindow.initAccountTabs();
                } else {
                    throw new Error('Nosto: failed to load accounts.');
                }
            }
        });
    },

    /**
     * Loads controller settings.
     *
     * @return void
     */
    loadSettings: function () {
        var me = this;
        Ext.Ajax.request({
            method: 'GET',
            url: '{url controller=NostoTagging action=loadSettings}',
            success: function(response) {
                var operation = Ext.decode(response.responseText);
                if (operation.success && operation.data) {
                    me.settings = operation.data;
                } else {
                    throw new Error('Nosto: failed to load settings.');
                }
            }
        });
    },

    /**
     * Register event handler for window.postMessage() messages from Nosto through which we handle account creation,
     * connection and deletion.
     *
     * @return void
     */
    postMessageListener: function () {
        var me = this;
        window.addEventListener('message', Ext.bind(me.receiveMessage, me), false);
    },

    /**
     * Window.postMessage() event handler.
     *
     * Handles the communication between the iframe and the plugin.
     *
     * @param event Object
     * @return void
     */
    receiveMessage: function(event) {
        var me = this,
            json,
            data,
            account,
            operation;

        // Check the origin to prevent cross-site scripting.
        if (event.origin !== decodeURIComponent(me.settings.postMessageOrigin)) {
            return;
        }
        // If the message does not start with '[Nosto]', then it is not for us.
        if ((''+event.data).substr(0, 7) !== '[Nosto]') {
            return;
        }

        json = (''+event.data).substr(7);
        data = Ext.decode(json);
        if (typeof data === 'object' && data.type) {
            account = me.mainWindow.getActiveAccount();
            if (!account) {
                throw new Error('Nosto: failed to determine active account.');
            }
            switch (data.type) {
                case 'newAccount':
                    account.save({
                        success: function(record, op) {
                            // why can't we get the model data binding to work?
                            if (op.resultSet && op.resultSet.records) {
                                record.set('url', op.resultSet.records[0].data.url);
                                me.mainWindow.reloadIframe(record);
                            } else {
                                throw new Error('Nosto: failed to create new account.');
                            }
                        }
                    });
                    break;

                case 'removeAccount':
                    account.destroy({
                        success: function(record, op) {
                            // why can't we get the model data binding to work?
                            if (op.resultSet && op.resultSet.records) {
                                record.set('url', op.resultSet.records[0].data.url);
                                me.mainWindow.reloadIframe(record);
                            } else {
                                throw new Error('Nosto: failed to delete account.');
                            }
                        }
                    });
                    break;

                case 'connectAccount':
                    Ext.Ajax.request({
                        method: 'POST',
                        url: '{url controller=NostoTagging action=connectAccount}',
                        params: {
                            shopId: account.get('shopId')
                        },
                        success: function(response) {
                            operation = Ext.decode(response.responseText);
                            if (operation.success && operation.data.redirect_url) {
                                window.location.href = operation.data.redirect_url;
                            } else {
                                throw new Error('Nosto: failed to handle account connection.');
                            }
                        }
                    });
                    break;

                default:
                    throw new Error('Nosto: invalid postMessage `type`.');
            }
        }
    }
});