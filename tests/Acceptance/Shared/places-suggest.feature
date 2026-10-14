Feature:
    I want to make sure that a logged user can search places through the autocomplete endpoint

    Scenario: An anonymous user can't get place suggestions
        Given place "madrid" named "Madrid, España" at "40.4168", "-3.7038" exists
        When I go to "/es/places/suggest?query=mad&kind=city"
        Then the response status should be 403

    Scenario: A logged user gets the places matching the query
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given place "madrid" named "Madrid, España" at "40.4168", "-3.7038" exists
        Given place "sevilla" named "Sevilla, España" at "37.3891", "-5.9845" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/places/suggest?query=mad&kind=city"
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        [{"value": "madrid", "label": "Madrid, España"}]
        """

    Scenario: A query shorter than 3 characters returns no suggestions
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given place "madrid" named "Madrid, España" at "40.4168", "-3.7038" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/places/suggest?query=ma&kind=city"
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        []
        """

    Scenario: An unknown kind falls back to any kind of place
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given place "madrid" named "Madrid, España" at "40.4168", "-3.7038" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/places/suggest?query=mad&kind=not-a-kind"
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        [{"value": "madrid", "label": "Madrid, España"}]
        """

    Scenario: Without a places provider the logged user gets no suggestions
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given place "madrid" named "Madrid, España" at "40.4168", "-3.7038" exists
        Given the places provider is disabled
        When I am logged as "user1@dediez.software"
        When I go to "/es/places/suggest?query=mad&kind=city"
        Then the response status should be 200
        Then I see a JSON that should be equal to:
        """
        []
        """
