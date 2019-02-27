/*
* Schema for registration and account form
*/

srchSchemaObj =
{
  title: "Search form",
  type: "object",
  properties: {
    q: {
      type: "string",
      format: "textarea",
      title: "Query (q)"
    },
    dbIds: {
      type: "string",
      title: "Database id's (dbIds)",
      description: "Use & to separate multiple values."
    },
    sortBy: {
      type: "select",
      title: "Sort by (sortBy)",
      enum: [
      "relevance",
      "library_plus_relevance",
      "creator",
      "title",
      "date",
      "language",
      "year",
      "librarycount"
      ]
    },
    heldBy: {
      type: "string",
      title: "Held by (heldBy)",
      description: "Leave empty for worldwide, use NLVRD for PPL only. Use & to separate multiple values."
    },
    datePublished: {
      type: "string",
      title: "Date or range published (datePublished)",
      description: "Examples: 2000 2000-2010 1990,1991"
    },
    materialType: {
      type: "string",
      title: "Material type (materialType)",
      description: "Use & to separate multiple values."
    },
    itemType: {
      type: "string",
      title: "Item type (itemType)",
      description: "Examples: artchap book image compfile. Use & to separate multiple values."
    },
    itemSubType: {
      type: "string",
      title: "Item subtype (itemSubType)",
      description: "Examples: digital dvd. Use & to separate multiple values."
    },
    itemsPerPage: {
      type: "string",
      title: "Items per page (itemsPerPage)",
      description: "Must be 10, 25, 50 or 100"
    },
    startIndex: {
      type: "string",
      title: "Start at number (startIndex)",
      description: "Specify the starting position for the search response.  Must be a multiple of itemsPerPage.  Index starts at zero."
    },
    responseType: {
      type: "select",
      title: "Response type (header Accept)",
      enum: [
      "application/json",
      "application/rdf+xml",
      "text/plain",
      "text/turtle ",
      "application/ld+json"
      ]
    }
  }
};
