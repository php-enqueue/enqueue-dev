<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

# Production settings

## Supervisord

As you may read in [quick tour](quick_tour.md) you have to run `enqueue:consume` in order to process messages 
The php process is not designed to work for a long time. So it has to quit periodically.
Or, the command may exit because of error or exception. 
Something has to bring it back and continue message consumption.
We advise you to use [Supervisord](http://supervisord.org/) for that. 
It starts processes and keep an eye on them while they are working. 


Here an example of supervisord configuration.
It runs four instances of `enqueue:consume` command.

```ini
[program:pf_message_consumer]
command=/path/to/bin/console --env=prod --no-debug --time-limit="now + 5 minutes" enqueue:consume
process_name=%(program_name)s_%(process_num)02d
numprocs=4
autostart=true
autorestart=true
startsecs=0
user=apache
redirect_stderr=true
```

_**Note**: Pay attention to `--time-limit` it tells the command to exit after 5 minutes._

[back to index](index.md)