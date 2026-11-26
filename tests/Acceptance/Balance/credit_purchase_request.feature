Feature:
    Ability for owners to request the purchase of CO2 credits for a compensation project

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists
        Given project "Item 1" with id "1111" and price per tonne "20" and cost per tonne "15" exists

    Scenario: As an owner I can request credits for a compensation project
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/credits/request"
        When I submit a form with "tonnes:10|legalName:Mi Empresa SL|taxId:B12345678|street:Calle Mayor 1|postalCode:28001|city:Madrid|country:ESP|email:facturas@example.com" and button "Continuar"
        Then the response status should be 200
        Then I see a text "181,50"
        Then I see a text "entiendo que se me cobrarán 181,50 € por los créditos"
        When I tick the checkbox "request_credit_purchase[termsAccepted]"
        When I submit a form with "" and button "Solicitar créditos"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Solicitud de créditos enviada correctamente"

    Scenario: The request is not sent without accepting the conditions
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/credits/request"
        When I submit a form with "tonnes:10|legalName:Mi Empresa SL|taxId:B12345678|street:Calle Mayor 1|postalCode:28001|city:Madrid|country:ESP|email:facturas@example.com" and button "Continuar"
        When I submit a form with "" and button "Solicitar créditos"
        Then the response status should be 200
        Then I see a text "Debe aceptar las condiciones para solicitar los créditos"
        Then I see a button with name "request_credit_purchase[request]"
        When I go to "/es/projects/1111"
        Then I see a text "Este proyecto no tiene transacciones de créditos"

    Scenario: The request is not sent when the amount changed since the confirmation
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/credits/request"
        When I submit a form with "tonnes:10|legalName:Mi Empresa SL|taxId:B12345678|street:Calle Mayor 1|postalCode:28001|city:Madrid|country:ESP|email:facturas@example.com" and button "Continuar"
        When I tick the checkbox "request_credit_purchase[termsAccepted]"
        When I submit a form with "acceptedTotal:100" and button "Solicitar créditos"
        Then the response status should be 200
        Then I see a text "El importe ha cambiado desde que lo consultaste. Revísalo y confirma de nuevo."
        Then I see a text "181,50"
        Then I see a button with name "request_credit_purchase[request]"
        When I go to "/es/projects/1111"
        Then I see a text "Este proyecto no tiene transacciones de créditos"

    Scenario: Credits cannot be requested for a project that is not a compensation project
        Given social project "Item 3" with id "3333" and price per tonne "20" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/3333/credits/request"
        Then the response status should be 404

    Scenario: The billing details are required
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/credits/request"
        When I submit a form with "tonnes:10|legalName:Mi Empresa SL|taxId:B12345678|street:|postalCode:28001|city:Madrid|country:ESP|email:facturas@example.com" and button "Continuar"
        Then the response status should be 200
        Then I see a button with name "request_credit_purchase[calculate]"
        Then I do not see a button with name "request_credit_purchase[request]"

    Scenario: Modifying the details goes back to the data step keeping what was typed
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/credits/request"
        When I submit a form with "tonnes:10|legalName:Otra Empresa SL|taxId:B12345678|street:Calle Mayor 1|postalCode:28001|city:Madrid|country:ESP|email:facturas@example.com" and button "Continuar"
        When I submit a form with "" and button "Modificar datos"
        Then the response status should be 200
        Then I see a button with name "request_credit_purchase[calculate]"
        Then I see a form field "legalName" with value "Otra Empresa SL"
        Then I see a form field "email" with value "facturas@example.com"

    Scenario: A company of another EU country with a valid VAT number is reverse charged
        Given european union country "Francia" with iso "FRA" and vat "20" and id "C-FR" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/credits/request"
        When I submit a form with "tonnes:10|legalName:Ma Société SARL|taxId:FR12345678901|street:Rue de Rivoli 1|postalCode:75001|city:Paris|country:FRA|email:factures@example.com" and button "Continuar"
        Then the response status should be 200
        Then I see a text "entiendo que se me cobrarán 150,00 € por los créditos"
        Then I see a text "Operación exenta por inversión del sujeto pasivo"

    Scenario: The billing details are prefilled from the payee of the account
        Given payee "Mi Empresa SL" with id "9999" for "account" "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/credits/request"
        Then the response status should be 200
        Then I see a form field "legalName" with value "Mi Empresa SL"
        Then I see a form field "email" with value "owner@dediez.software"

    Scenario: The billing details are prefilled from the previous request
        Given payee "Mi Empresa SL" with id "9999" for "account" "666" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes billed to "Anterior SL" with vat "21" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/credits/request"
        Then the response status should be 200
        Then I see a form field "legalName" with value "Anterior SL"
        Then I see a form field "email" with value "facturas@example.com"

    Scenario: A project without cost per tonne can be requested without an estimated price
        Given project "Item 2" with id "2222" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/2222/credits/request"
        Then I see a text "no podemos darte un precio estimado a cobrar"
        When I submit a form with "tonnes:10|legalName:Mi Empresa SL|taxId:B12345678|street:Calle Mayor 1|postalCode:28001|city:Madrid|country:ESP|email:facturas@example.com" and button "Continuar"
        Then the response status should be 200
        Then I see a text "no podemos darte un precio estimado a cobrar"
        Then I see a text "entiendo que se me cobrará el importe de los créditos que se me comunicará"
        When I tick the checkbox "request_credit_purchase[termsAccepted]"
        When I submit a form with "" and button "Solicitar créditos"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Solicitud de créditos enviada correctamente"

    Scenario: The admin sees the requested amount and the billing details when validating
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes billed to "Mi Empresa SL" with vat "21" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/validate"
        Then the response status should be 200
        Then I see a text "Total solicitado"
        Then I see a text "181,50 €"
        Then I see a text "Mi Empresa SL"

    Scenario: The owner receives an email with the summary of the request
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/credits/request"
        When I submit a form with "tonnes:10|legalName:Mi Empresa SL|taxId:B12345678|street:Calle Mayor 1|postalCode:28001|city:Madrid|country:ESP|email:facturas@example.com" and button "Continuar"
        When I tick the checkbox "request_credit_purchase[termsAccepted]"
        When I submit a form with "" and button "Solicitar créditos"
        When the queued messages are processed
        Then an email with subject "Hemos recibido su solicitud de créditos" was sent to "owner@dediez.software"
        Then the email with subject "Hemos recibido su solicitud de créditos" contains "181,50 €"
        Then the email with subject "Hemos recibido su solicitud de créditos" contains "Recibirá la factura en facturas@example.com"

    Scenario: As an owner I can cancel a pending request
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/purchases/2222/cancel"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Solicitud de créditos cancelada"
